<div class="shop-css-editor">
    {{-- CodeMirror CSS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">

    {{-- Header --}}
    <div class="css-editor-header">
        <div class="css-editor-header-info">
            <h2 class="css-editor-title">
                Edytor CSS
                @if($shop)
                    <span class="css-editor-shop-name">{{ $shop->name }}</span>
                @endif
            </h2>
            @if($filePath)
                <div class="css-editor-file-path">
                    <span class="css-editor-file-path-label">Plik:</span>
                    <code class="css-editor-file-path-value">{{ $filePath }}</code>
                </div>
            @endif
        </div>

        <div class="css-editor-header-actions">
            <button type="button"
                    wire:click="closeEditor"
                    class="btn-enterprise-secondary btn-enterprise-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Powrot
            </button>
        </div>
    </div>

    {{-- Messages --}}
    @if($errorMessage)
        <div class="css-editor-alert css-editor-alert-error">
            <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    @if($successMessage)
        <div class="css-editor-alert css-editor-alert-success">
            <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $successMessage }}</span>
        </div>
    @endif

    {{-- Editor Container --}}
    @if($shop && $this->isFtpConfigured() && !$lockInfo)
        <div class="css-editor-container">
            {{-- Toolbar --}}
            <div class="css-editor-toolbar">
                <div class="css-editor-toolbar-left">
                    <button type="button"
                            wire:click="loadCss"
                            wire:loading.attr="disabled"
                            wire:target="loadCss"
                            class="btn-enterprise-secondary btn-enterprise-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="loadCss">Odswiez</span>
                        <span wire:loading wire:target="loadCss">Ladowanie...</span>
                    </button>

                    <button type="button"
                            wire:click="saveCss"
                            wire:loading.attr="disabled"
                            wire:target="saveCss"
                            class="btn-enterprise-primary btn-enterprise-sm"
                            @if(!$this->hasUnsavedChanges) disabled @endif>
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span wire:loading.remove wire:target="saveCss">Zapisz</span>
                        <span wire:loading wire:target="saveCss">Zapisywanie...</span>
                    </button>
                </div>

                <div class="css-editor-toolbar-right">
                    <div class="css-editor-stats">
                        <span class="css-editor-stat">
                            <span class="css-editor-stat-label">Rozmiar:</span>
                            <span class="css-editor-stat-value">{{ $this->cssSize }}</span>
                        </span>
                        <span class="css-editor-stat">
                            <span class="css-editor-stat-label">Linie:</span>
                            <span class="css-editor-stat-value">{{ $this->lineCount }}</span>
                        </span>
                        @if($this->hasUnsavedChanges)
                            <span class="css-editor-unsaved-badge">Niezapisane zmiany</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Code Editor --}}
            <div class="css-editor-code-wrapper"
                 x-data="cssEditorInit()"
                 x-init="initEditor()">
                <textarea
                    id="css-editor-textarea"
                    wire:model.live.debounce.500ms="cssContent"
                    class="css-editor-textarea"
                    spellcheck="false"
                    @if($isLoading) disabled @endif
                ></textarea>
            </div>

            {{-- Footer Info --}}
            <div class="css-editor-footer">
                @if($lastModified)
                    <span class="css-editor-footer-item">
                        Ostatnia modyfikacja: {{ $lastModified }}
                    </span>
                @endif
                @if($shop->css_last_deployed_at)
                    <span class="css-editor-footer-item">
                        Ostatni deploy: {{ $shop->css_last_deployed_at->format('Y-m-d H:i:s') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="loadCss, saveCss" class="css-editor-loading-overlay">
            <div class="css-editor-loading-spinner"></div>
            <span wire:loading wire:target="loadCss">Ladowanie CSS...</span>
            <span wire:loading wire:target="saveCss">Zapisywanie CSS...</span>
        </div>
    @elseif($lockInfo)
        {{-- Lock Warning --}}
        <div class="css-editor-lock-warning">
            <svg class="lock-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <div class="lock-info">
                <h3>Plik jest zablokowany</h3>
                <p>Edytowany przez: {{ $lockInfo['user_name'] ?? 'Unknown' }}</p>
                <p>Od: {{ $lockInfo['locked_at'] ?? 'Unknown' }}</p>
            </div>
        </div>
    @endif

    {{-- Confirmation Modal --}}
    <div x-data="{ showConfirm: false }"
         @confirm-refresh.window="showConfirm = true"
         x-show="showConfirm"
         x-cloak
         class="css-editor-modal-overlay">
        <div class="css-editor-modal">
            <h3 class="css-editor-modal-title">Odrzucic zmiany?</h3>
            <p class="css-editor-modal-text">
                Masz niezapisane zmiany. Czy na pewno chcesz odswiezyc plik i odrzucic zmiany?
            </p>
            <div class="css-editor-modal-actions">
                <button type="button"
                        @click="showConfirm = false"
                        class="btn-enterprise-secondary btn-enterprise-sm">
                    Anuluj
                </button>
                <button type="button"
                        @click="showConfirm = false; $wire.dispatch('confirm-refresh-accepted')"
                        class="btn-enterprise-danger btn-enterprise-sm">
                    Odrzuc zmiany
                </button>
            </div>
        </div>
    </div>

{{-- CodeMirror JS --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script>
function cssEditorInit() {
    return {
        editor: null,

        initEditor() {
            const textarea = document.getElementById('css-editor-textarea');
            if (!textarea) return;

            this.editor = CodeMirror.fromTextArea(textarea, {
                mode: 'css',
                theme: 'dracula',
                lineNumbers: true,
                lineWrapping: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                tabSize: 2,
                indentWithTabs: false,
                extraKeys: {
                    'Ctrl-S': () => {
                        @this.saveCss();
                    },
                    'Cmd-S': () => {
                        @this.saveCss();
                    }
                }
            });

            const initialContent = @this.cssContent || '';
            if (initialContent) {
                this.editor.setValue(initialContent);
            }

            this.editor.on('change', (cm) => {
                const value = cm.getValue();
                @this.set('cssContent', value);
            });

            Livewire.on('css-loaded', () => {
                if (this.editor && @this.cssContent) {
                    this.editor.setValue(@this.cssContent);
                }
            });

            Livewire.on('css-saved', () => {
                if (this.editor) {
                    this.editor.refresh();
                }
            });
        }
    }
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
</div>
