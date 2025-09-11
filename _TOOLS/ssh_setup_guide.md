# 🔐 SSH Setup Guide dla MyDevil

## OPCJA 1: Klucz SSH (ZALECANA)

### 1. Generowanie klucza SSH
```powershell
# Utwórz katalog jeśli nie istnieje
$SSHDir = "d:\OneDrive - MPP TRADE\Dokumenty\.ssh"
if (!(Test-Path $SSHDir)) { New-Item -ItemType Directory -Path $SSHDir -Force }

# Generuj klucz SSH
ssh-keygen -t rsa -b 4096 -f "$SSHDir\PPM_mydevil_rsa" -N "" -C "PPM-CC-Laravel@MyDevil"
```

### 2. Dodanie klucza do MyDevil
1. Zaloguj się do panelu MyDevil
2. Idź do: **Panel → SSH → Klucze SSH**
3. Dodaj zawartość pliku `PPM_mydevil_rsa.pub`

### 3. Testowanie połączenia
```powershell
ssh -i "d:\OneDrive - MPP TRADE\Dokumenty\.ssh\PPM_mydevil_rsa" mpptrade@s53.mydevil.net
```

---

## OPCJA 2: PuTTY z automatyzacją

### 1. Instalacja PuTTY
- Download z: https://www.putty.org/
- Potrzebujemy: `putty.exe` i `plink.exe`

### 2. Konfiguracja sesji PuTTY
1. Uruchom PuTTY
2. Host Name: `s53.mydevil.net`
3. Port: `22`
4. Zapisz sesję jako "MyDevil-PPM"

### 3. PowerShell z plink
```powershell
# Przykład połączenia z plink
$PlinkPath = "C:\Program Files\PuTTY\plink.exe"
$Command = "hostname && whoami && php -v"
$Password = "Znighcnh861001"

# Używanie plink z hasłem
echo $Password | & $PlinkPath -ssh mpptrade@s53.mydevil.net -batch $Command
```

---

## OPCJA 3: WinSCP Automatyzacja

### 1. Instalacja WinSCP
- Download z: https://winscp.net/

### 2. PowerShell z WinSCP .NET Assembly
```powershell
# Dodaj WinSCP assembly
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"

# Konfiguracja sesji
$SessionOptions = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Sftp
    HostName = "s53.mydevil.net"
    UserName = "mpptrade"
    Password = "Znighcnh861001"
}

# Połączenie i transfer
$Session = New-Object WinSCP.Session
try {
    $Session.Open($SessionOptions)
    # Upload plików
    $Session.PutFiles("local-path/*", "/domains/ppm.mpptrade.pl/public_html/")
}
finally {
    $Session.Dispose()
}
```

---

## ZALECENIA

1. **UŻYJ OPCJI 1** - Klucz SSH jest najbezpieczniejszy
2. **Backup kluczy** - Przechowuj klucze w bezpiecznym miejscu
3. **Nie hardcode haseł** - Używaj zmiennych środowiskowych
4. **Testuj połączenia** - Przed automatyzacją sprawdź ręcznie

---

## Następne kroki
1. Wybierz opcję automatyzacji
2. Skonfiguruj wybraną metodę
3. Przetestuj połączenie
4. Utwórz skrypty deployment