using System.Runtime.InteropServices;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;

namespace SubiektApi;

/// <summary>
/// Sfera GT COM/OLE Wrapper for safe product operations in Subiekt GT.
///
/// CRITICAL: This uses Sfera API for write operations, not direct SQL!
/// Direct SQL INSERT/UPDATE is FORBIDDEN by InsERT documentation.
///
/// Requirements:
/// - Sfera GT license on the Windows Server
/// - COM+ interop enabled
/// - API operator created in Subiekt GT (Panel GT -> Administracja -> Operatorzy)
/// </summary>
public class SferaService : IDisposable
{
    private readonly ILogger<SferaService> _logger;
    private readonly SferaConfig _config;
    private dynamic? _gt;
    private dynamic? _subiekt;
    private bool _initialized;
    private bool _disposed;

    public SferaService(IOptions<SferaConfig> config, ILogger<SferaService> logger)
    {
        _config = config.Value;
        _logger = logger;
    }

    /// <summary>
    /// Initialize connection to Sfera GT.
    /// Must be called before any operations.
    /// </summary>
    public async Task<bool> InitializeAsync()
    {
        if (_initialized)
            return true;

        return await Task.Run(() =>
        {
            try
            {
                _logger.LogInformation("Initializing Sfera GT connection...");

                // Create COM object for Insert.gt
                var progId = "Insert.gt";
                var type = Type.GetTypeFromProgID(progId);

                if (type == null)
                {
                    _logger.LogError("Sfera GT COM component not registered. ProgID: {ProgId}", progId);
                    throw new SferaException("Sfera GT COM component 'Insert.gt' not registered on this machine");
                }

                _gt = Activator.CreateInstance(type);
                if (_gt == null)
                {
                    throw new SferaException("Failed to create Sfera GT COM instance");
                }

                // Configure connection
                _gt.Produkt = 1;  // 1 = Subiekt GT
                _gt.Serwer = _config.Server;
                _gt.Baza = _config.Database;
                _gt.Autentykacja = _config.UseWindowsAuth ? 0 : 1;  // 0 = Windows Auth, 1 = SQL Auth

                if (!_config.UseWindowsAuth)
                {
                    _gt.Uzytkownik = _config.User;
                    _gt.UzytkownikHaslo = _config.Password;
                }

                // Operator for Sfera API (created in Subiekt GT)
                _gt.Operator = _config.Operator;
                _gt.OperatorHaslo = _config.OperatorPassword;

                _logger.LogInformation("Launching Sfera GT (Server: {Server}, Database: {Database})...",
                    _config.Server, _config.Database);

                // Launch Sfera GT
                // Flags: 0 = hidden, 4 = background operation
                _subiekt = _gt.Uruchom(0, 4);

                if (_subiekt == null)
                {
                    throw new SferaException("Failed to launch Sfera GT - Uruchom returned null");
                }

                _initialized = true;
                _logger.LogInformation("Sfera GT initialized successfully");
                return true;
            }
            catch (COMException ex)
            {
                _logger.LogError(ex, "COM error initializing Sfera GT: {Message}", ex.Message);
                throw new SferaException($"COM error: {ex.Message}", ex);
            }
            catch (Exception ex) when (ex is not SferaException)
            {
                _logger.LogError(ex, "Failed to initialize Sfera GT: {Message}", ex.Message);
                throw new SferaException($"Initialization failed: {ex.Message}", ex);
            }
        });
    }

    /// <summary>
    /// Check if Sfera GT is initialized and connected.
    /// </summary>
    public bool IsConnected => _initialized && _subiekt != null;

    /// <summary>
    /// Get TowaryManager for product operations.
    /// </summary>
    public dynamic GetTowaryManager()
    {
        EnsureInitialized();
        return _subiekt!.TowaryManager;
    }

    /// <summary>
    /// Get KontrahenciManager for customer operations.
    /// </summary>
    public dynamic GetKontrahenciManager()
    {
        EnsureInitialized();
        return _subiekt!.KontrahenciManager;
    }

    /// <summary>
    /// Get DokumentyManager for document operations.
    /// </summary>
    public dynamic GetDokumentyManager()
    {
        EnsureInitialized();
        return _subiekt!.DokumentyManager;
    }

    /// <summary>
    /// Get MagazynyManager for warehouse operations.
    /// </summary>
    public dynamic GetMagazynyManager()
    {
        EnsureInitialized();
        return _subiekt!.MagazynyManager;
    }

    /// <summary>
    /// Execute action within Sfera context with error handling.
    /// </summary>
    public async Task<T> ExecuteAsync<T>(Func<dynamic, T> action)
    {
        await InitializeAsync();

        return await Task.Run(() =>
        {
            try
            {
                return action(_subiekt!);
            }
            catch (COMException ex)
            {
                _logger.LogError(ex, "Sfera COM error during operation: {Message}", ex.Message);
                throw new SferaException($"Sfera operation failed: {ex.Message}", ex);
            }
        });
    }

    /// <summary>
    /// Test connection to Sfera GT.
    /// </summary>
    public async Task<SferaHealthResult> TestConnectionAsync()
    {
        var result = new SferaHealthResult { Timestamp = DateTime.Now };

        try
        {
            var sw = System.Diagnostics.Stopwatch.StartNew();
            await InitializeAsync();
            sw.Stop();

            result.Success = true;
            result.Status = "connected";
            result.ResponseTimeMs = sw.Elapsed.TotalMilliseconds;
            result.Server = _config.Server;
            result.Database = _config.Database;

            // Try to get basic info
            try
            {
                var version = _subiekt!.Wersja?.ToString() ?? "unknown";
                result.Version = version;
            }
            catch
            {
                result.Version = "unable to retrieve";
            }
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.Status = "error";
            result.Error = ex.Message;
        }

        return result;
    }

    private void EnsureInitialized()
    {
        if (!_initialized || _subiekt == null)
        {
            throw new SferaException("Sfera GT not initialized. Call InitializeAsync() first.");
        }
    }

    public void Dispose()
    {
        Dispose(true);
        GC.SuppressFinalize(this);
    }

    protected virtual void Dispose(bool disposing)
    {
        if (_disposed)
            return;

        if (disposing)
        {
            try
            {
                if (_subiekt != null)
                {
                    _logger.LogInformation("Closing Sfera GT connection...");
                    // Close Sfera GT session
                    try { _subiekt.Zakoncz(); } catch { }
                    Marshal.ReleaseComObject(_subiekt);
                    _subiekt = null;
                }

                if (_gt != null)
                {
                    Marshal.ReleaseComObject(_gt);
                    _gt = null;
                }

                _initialized = false;
                _logger.LogInformation("Sfera GT connection closed");
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Error during Sfera GT cleanup");
            }
        }

        _disposed = true;
    }

    ~SferaService()
    {
        Dispose(false);
    }
}

/// <summary>
/// Configuration for Sfera GT connection.
/// </summary>
public class SferaConfig
{
    public string Server { get; set; } = @"(local)\INSERTGT";
    public string Database { get; set; } = "FIRMA";
    public bool UseWindowsAuth { get; set; } = false;
    public string User { get; set; } = "sa";
    public string Password { get; set; } = "";
    public string Operator { get; set; } = "API";
    public string OperatorPassword { get; set; } = "";
    public int Timeout { get; set; } = 60;
}

/// <summary>
/// Health check result for Sfera GT connection.
/// </summary>
public class SferaHealthResult
{
    public bool Success { get; set; }
    public string Status { get; set; } = "";
    public string? Error { get; set; }
    public string? Server { get; set; }
    public string? Database { get; set; }
    public string? Version { get; set; }
    public double ResponseTimeMs { get; set; }
    public DateTime Timestamp { get; set; }
}

/// <summary>
/// Custom exception for Sfera GT operations.
/// </summary>
public class SferaException : Exception
{
    public SferaException(string message) : base(message) { }
    public SferaException(string message, Exception innerException) : base(message, innerException) { }
}
