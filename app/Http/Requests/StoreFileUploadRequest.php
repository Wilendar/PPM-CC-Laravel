<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * StoreFileUploadRequest - FAZA C: Validation dla upload dokumentów
 * 
 * Zabezpiecza upload plików dla różnych encji:
 * - File validation (types, size, security)
 * - Access level validation
 * - Business rules (file type classification)
 * - Security validation (MIME type checking)
 * 
 * @package App\Http\Requests
 * @since FAZA C - Media & Relations Implementation
 */
class StoreFileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to upload files
        // Admin, Manager, Editor, Warehouseman can upload files
        return $this->user() && in_array($this->user()->role, ['admin', 'manager', 'editor', 'warehouseman']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Core file validation
            'file' => [
                'required',
                File::types(['pdf', 'xlsx', 'xls', 'docx', 'doc', 'zip', 'rar', '7z', 'xml', 'txt', 'csv'])
                    ->max(50 * 1024), // 50MB max for documents
            ],
            
            // Uploadable polymorphic relationship
            'uploadable_type' => [
                'required',
                'string',
                'in:App\\Models\\Container,App\\Models\\Order,App\\Models\\Product,App\\Models\\User',
            ],
            'uploadable_id' => [
                'required',
                'integer',
                'min:1',
            ],
            
            // File classification
            'file_type' => [
                'nullable',
                'string',
                'in:document,spreadsheet,archive,certificate,manual,other',
            ],
            
            // Access control
            'access_level' => [
                'required',
                'string',
                'in:admin,manager,all',
            ],
            
            // Optional metadata
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Plik jest wymagany.',
            'file.mimes' => 'Dozwolone formaty: PDF, Excel (XLSX/XLS), Word (DOCX/DOC), ZIP, RAR, 7Z, XML, TXT, CSV.',
            'file.max' => 'Maksymalny rozmiar pliku to 50MB.',
            
            'uploadable_type.required' => 'Typ obiektu jest wymagany.',
            'uploadable_type.in' => 'Nieprawidłowy typ obiektu.',
            'uploadable_id.required' => 'ID obiektu jest wymagane.',
            'uploadable_id.integer' => 'ID obiektu musi być liczbą.',
            
            'file_type.in' => 'Nieprawidłowy typ pliku.',
            'access_level.required' => 'Poziom dostępu jest wymagany.',
            'access_level.in' => 'Nieprawidłowy poziom dostępu.',
            
            'description.max' => 'Opis może mieć maksymalnie 1000 znaków.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'plik',
            'uploadable_type' => 'typ obiektu',
            'uploadable_id' => 'ID obiektu',
            'file_type' => 'typ pliku',
            'access_level' => 'poziom dostępu',
            'description' => 'opis',
            'metadata' => 'metadane',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate uploadable object exists
            $this->validateUploadableExists($validator);
            
            // Validate access level permissions
            $this->validateAccessLevelPermissions($validator);
            
            // Validate file size for specific types
            $this->validateFileSizeForType($validator);
        });
    }

    /**
     * Validate that uploadable object exists
     */
    private function validateUploadableExists($validator): void
    {
        $type = $this->input('uploadable_type');
        $id = $this->input('uploadable_id');
        
        if ($type && $id) {
            $model = app($type);
            $exists = $model::where('id', $id)->exists();
            
            if (!$exists) {
                $validator->errors()->add('uploadable_id', 'Wybrany obiekt nie istnieje.');
            }
        }
    }

    /**
     * Validate access level permissions
     */
    private function validateAccessLevelPermissions($validator): void
    {
        $accessLevel = $this->input('access_level');
        $userRole = $this->user()?->role;
        
        // Only admin can set admin access level
        if ($accessLevel === 'admin' && $userRole !== 'admin') {
            $validator->errors()->add('access_level', 'Tylko administrator może ustawić poziom dostępu "admin".');
        }
        
        // Only admin and manager can set manager access level
        if ($accessLevel === 'manager' && !in_array($userRole, ['admin', 'manager'])) {
            $validator->errors()->add('access_level', 'Tylko administrator i menedżer mogą ustawić poziom dostępu "manager".');
        }
    }

    /**
     * Validate file size for specific types
     */
    private function validateFileSizeForType($validator): void
    {
        $file = $this->file('file');
        $fileType = $this->input('file_type');
        
        if ($file && $fileType) {
            $fileSize = $file->getSize();
            $maxSizes = [
                'document' => 10 * 1024 * 1024,    // 10MB for documents
                'spreadsheet' => 20 * 1024 * 1024, // 20MB for spreadsheets
                'archive' => 50 * 1024 * 1024,     // 50MB for archives
                'certificate' => 5 * 1024 * 1024,  // 5MB for certificates
                'manual' => 10 * 1024 * 1024,      // 10MB for manuals
                'other' => 10 * 1024 * 1024,       // 10MB for other files
            ];
            
            if (isset($maxSizes[$fileType]) && $fileSize > $maxSizes[$fileType]) {
                $maxSizeMB = $maxSizes[$fileType] / (1024 * 1024);
                $validator->errors()->add('file', "Maksymalny rozmiar dla typu '{$fileType}' to {$maxSizeMB}MB.");
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize uploadable_type to full class name
        if ($this->has('uploadable_type')) {
            $type = $this->input('uploadable_type');
            
            // Allow short names and convert to full class names
            $typeMap = [
                'container' => 'App\\Models\\Container',
                'Container' => 'App\\Models\\Container',
                'order' => 'App\\Models\\Order',
                'Order' => 'App\\Models\\Order',
                'product' => 'App\\Models\\Product',
                'Product' => 'App\\Models\\Product',
                'user' => 'App\\Models\\User',
                'User' => 'App\\Models\\User',
            ];
            
            if (isset($typeMap[$type])) {
                $this->merge([
                    'uploadable_type' => $typeMap[$type],
                ]);
            }
        }
        
        // Auto-detect file_type if not provided
        if (!$this->has('file_type') && $this->hasFile('file')) {
            $file = $this->file('file');
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            
            $fileType = $this->detectFileType($mimeType, $extension);
            
            $this->merge([
                'file_type' => $fileType,
            ]);
        }
        
        // Set default access_level based on user role
        if (!$this->has('access_level')) {
            $userRole = $this->user()?->role;
            
            $defaultAccessLevel = match ($userRole) {
                'admin' => 'manager',      // Admin defaults to manager level
                'manager' => 'manager',    // Manager defaults to manager level  
                'editor' => 'all',        // Editor defaults to all level
                'warehouseman' => 'all',  // Warehouseman defaults to all level
                default => 'all',         // Others default to all level
            };
            
            $this->merge([
                'access_level' => $defaultAccessLevel,
            ]);
        }
    }

    /**
     * Auto-detect file type based on MIME type and extension
     */
    private function detectFileType(string $mimeType, string $extension): string
    {
        return match (true) {
            str_contains($mimeType, 'pdf') => 'document',
            str_contains($mimeType, 'word') => 'document',
            str_contains($mimeType, 'text') => 'document',
            in_array($extension, ['txt', 'xml']) => 'document',
            str_contains($mimeType, 'excel') => 'spreadsheet',
            str_contains($mimeType, 'spreadsheet') => 'spreadsheet',
            in_array($extension, ['xlsx', 'xls', 'csv']) => 'spreadsheet',
            str_contains($mimeType, 'zip') => 'archive',
            in_array($extension, ['zip', 'rar', '7z']) => 'archive',
            str_contains($extension, 'cert') => 'certificate',
            str_contains($extension, 'manual') => 'manual',
            default => 'other',
        };
    }
}