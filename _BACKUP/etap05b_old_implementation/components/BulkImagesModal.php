<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Bulk Images Modal Component
 *
 * Umożliwia masowe przypisywanie zdjęć do wielu wariantów jednocześnie
 * z obsługą upload multiple files
 *
 * @property array $selectedVariantIds IDs wariantów do przypisania zdjęć
 * @property array $uploadedImages Tablice uploadowanych plików
 * @property string $assignmentType Typ przypisania: add|replace|set_main
 * @property bool $showModal Widoczność modala
 */
class BulkImagesModal extends Component
{
    use WithFileUploads;

    public array $selectedVariantIds = [];

    #[Validate(['uploadedImages.*' => 'image|max:5120'])] // 5MB max per image
    public array $uploadedImages = [];

    public string $assignmentType = 'add';
    public bool $showModal = false;

    /**
     * Custom validation messages
     */
    protected function messages(): array
    {
        return [
            'uploadedImages.*.image' => 'Plik musi być obrazem (JPG, PNG, GIF, SVG)',
            'uploadedImages.*.max' => 'Maksymalny rozmiar obrazu to 5MB',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE HOOKS
    |--------------------------------------------------------------------------
    */

    /**
     * Component mount (no parameters - DI safe)
     */
    public function mount(): void
    {
        // Initialize component without parameters
        // Modal opens via event dispatch
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Open modal with selected variant IDs
     */
    #[On('open-bulk-images-modal')]
    public function openModal($variantIds): void
    {
        $this->selectedVariantIds = (array) $variantIds;
        $this->uploadedImages = [];
        $this->assignmentType = 'add';
        $this->showModal = true;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get selected variants
     */
    #[Computed]
    public function variants(): Collection
    {
        return ProductVariant::whereIn('id', $this->selectedVariantIds)
            ->with(['product', 'images'])
            ->get();
    }

    /**
     * Get total uploaded images count
     */
    #[Computed]
    public function totalImages(): int
    {
        return count($this->uploadedImages);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Remove image from upload queue
     */
    public function removeImage(int $index): void
    {
        if (isset($this->uploadedImages[$index])) {
            unset($this->uploadedImages[$index]);
            $this->uploadedImages = array_values($this->uploadedImages); // Reindex array
        }
    }

    /**
     * Assign uploaded images to variants with transaction safety
     */
    public function assign(): void
    {
        // Walidacja podstawowa
        if (empty($this->uploadedImages)) {
            $this->addError('uploadedImages', 'Nie wybrano żadnych zdjęć');
            return;
        }

        if (count($this->uploadedImages) > 10) {
            $this->addError('uploadedImages', 'Maksymalnie można przesłać 10 zdjęć na raz');
            return;
        }

        // Walidacja plików
        $this->validate();

        try {
            DB::transaction(function () {
                $totalAssigned = 0;

                foreach ($this->selectedVariantIds as $variantId) {
                    // Handle różnych typów przypisania
                    if ($this->assignmentType === 'replace') {
                        // Usuń stare zdjęcia
                        $oldImages = VariantImage::where('variant_id', $variantId)->get();
                        foreach ($oldImages as $oldImage) {
                            $oldImage->deleteFile();
                            $oldImage->delete();
                        }
                    }

                    // Get current max position
                    $maxPosition = VariantImage::where('variant_id', $variantId)
                        ->max('position') ?? 0;

                    // Upload i zapisz każde zdjęcie
                    foreach ($this->uploadedImages as $index => $uploadedImage) {
                        // Store file
                        $filename = $uploadedImage->hashName();
                        $path = $uploadedImage->store('variants', 'public');

                        // Determine if this should be cover image
                        $isCover = false;
                        if ($this->assignmentType === 'set_main' && $index === 0) {
                            // First image becomes cover
                            // Unset existing covers for this variant
                            VariantImage::where('variant_id', $variantId)
                                ->update(['is_cover' => false]);
                            $isCover = true;
                        } elseif ($this->assignmentType === 'replace' && $index === 0) {
                            // First image after replace becomes cover
                            $isCover = true;
                        }

                        // Create variant_images record
                        VariantImage::create([
                            'variant_id' => $variantId,
                            'filename' => $filename,
                            'path' => $path,
                            'is_cover' => $isCover,
                            'position' => $maxPosition + $index + 1,
                        ]);

                        $totalAssigned++;
                    }
                }

                session()->flash('message', sprintf(
                    'Przypisano %d zdjęć do %d wariantów',
                    count($this->uploadedImages),
                    count($this->selectedVariantIds)
                ));
            });

            // Dispatch event to refresh variants list
            $this->dispatch('refresh-variants');

            $this->close();

        } catch (\Exception $e) {
            $this->addError('assign', 'Błąd podczas przypisywania zdjęć: ' . $e->getMessage());
        }
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->showModal = false;
        $this->reset(['selectedVariantIds', 'uploadedImages', 'assignmentType']);
        $this->resetValidation();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.variants.bulk-images-modal');
    }
}
