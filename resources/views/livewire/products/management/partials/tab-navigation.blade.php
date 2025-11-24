{{-- Enterprise Tab Navigation --}}
<div class="tabs-enterprise">
    <button class="tab-enterprise {{ $activeTab === 'basic' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('basic')">
        <i class="fas fa-info-circle icon"></i>
        <span>Informacje podstawowe</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'description' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('description')">
        <i class="fas fa-align-left icon"></i>
        <span>Opisy i SEO</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'physical' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('physical')">
        <i class="fas fa-box icon"></i>
        <span>Właściwości fizyczne</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'attributes' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('attributes')">
        <i class="fas fa-tags icon"></i>
        <span>Atrybuty</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'prices' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('prices')">
        <i class="fas fa-dollar-sign icon"></i>
        <span>Ceny</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'stock' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('stock')">
        <i class="fas fa-warehouse icon"></i>
        <span>Stany magazynowe</span>
    </button>

</div>
