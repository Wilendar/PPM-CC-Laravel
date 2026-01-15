{{-- Block Preview Item - ETAP_07f FAZA 5 --}}
{{-- Renders a single block in template preview --}}
@php
    $blockType = $block['type'] ?? 'text';
    $content = $block['content'] ?? '';
    $properties = $block['properties'] ?? [];
@endphp

@switch($blockType)
    @case('heading')
        @php
            $level = $properties['level'] ?? 2;
            $alignment = $properties['alignment'] ?? 'left';
        @endphp
        <{{ 'h' . $level }} class="text-{{ $alignment }} {{ $level == 1 ? 'text-2xl font-bold' : ($level == 2 ? 'text-xl font-semibold' : 'text-lg font-medium') }} text-gray-900 mb-4">
            {{ $content }}
        </{{ 'h' . $level }}>
        @break

    @case('paragraph')
    @case('text')
        @php
            $alignment = $properties['alignment'] ?? 'left';
        @endphp
        <p class="text-{{ $alignment }} text-gray-700 mb-4 leading-relaxed">
            {!! nl2br(e($content)) !!}
        </p>
        @break

    @case('image')
        @php
            $src = $properties['src'] ?? '';
            $alt = $properties['alt'] ?? '';
            $width = $properties['width'] ?? 'full';
        @endphp
        @if($src)
            <figure class="mb-4">
                <img
                    src="{{ $src }}"
                    alt="{{ $alt }}"
                    class="rounded-lg {{ $width === 'full' ? 'w-full' : ($width === 'half' ? 'w-1/2' : 'w-auto') }}"
                    loading="lazy"
                >
                @if($alt)
                    <figcaption class="text-sm text-gray-500 mt-2 text-center">{{ $alt }}</figcaption>
                @endif
            </figure>
        @else
            <div class="mb-4 p-8 bg-gray-100 rounded-lg text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm">Miejsce na obraz</span>
            </div>
        @endif
        @break

    @case('list')
        @php
            $listType = $properties['listType'] ?? 'unordered';
            $items = $properties['items'] ?? [];
        @endphp
        @if($listType === 'ordered')
            <ol class="list-decimal list-inside mb-4 text-gray-700 space-y-1">
                @foreach($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        @else
            <ul class="list-disc list-inside mb-4 text-gray-700 space-y-1">
                @foreach($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        @break

    @case('table')
        @php
            $headers = $properties['headers'] ?? [];
            $rows = $properties['rows'] ?? [];
        @endphp
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                @if(!empty($headers))
                    <thead class="bg-gray-100">
                        <tr>
                            @foreach($headers as $header)
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b border-gray-200">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody class="divide-y divide-gray-200">
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @break

    @case('divider')
        @php
            $style = $properties['style'] ?? 'solid';
        @endphp
        <hr class="my-6 border-gray-300 {{ $style === 'dashed' ? 'border-dashed' : ($style === 'dotted' ? 'border-dotted' : 'border-solid') }}">
        @break

    @case('spacer')
        @php
            $height = $properties['height'] ?? 'medium';
            $heightClass = match($height) {
                'small' => 'h-4',
                'large' => 'h-12',
                default => 'h-8'
            };
        @endphp
        <div class="{{ $heightClass }}"></div>
        @break

    @case('quote')
    @case('blockquote')
        @php
            $author = $properties['author'] ?? '';
        @endphp
        <blockquote class="mb-4 pl-4 border-l-4 border-gray-300 italic text-gray-600">
            <p>{{ $content }}</p>
            @if($author)
                <cite class="block mt-2 text-sm text-gray-500 not-italic">- {{ $author }}</cite>
            @endif
        </blockquote>
        @break

    @case('callout')
    @case('alert')
        @php
            $variant = $properties['variant'] ?? 'info';
            $colorClasses = match($variant) {
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'error', 'danger' => 'bg-red-50 border-red-200 text-red-800',
                default => 'bg-blue-50 border-blue-200 text-blue-800'
            };
        @endphp
        <div class="mb-4 p-4 rounded-lg border {{ $colorClasses }}">
            {{ $content }}
        </div>
        @break

    @case('code')
        @php
            $language = $properties['language'] ?? '';
        @endphp
        <pre class="mb-4 p-4 bg-gray-900 text-gray-100 rounded-lg overflow-x-auto text-sm"><code>{{ $content }}</code></pre>
        @break

    @case('video')
        @php
            $src = $properties['src'] ?? '';
            $provider = $properties['provider'] ?? 'youtube';
        @endphp
        @if($src)
            <div class="mb-4 aspect-video">
                <iframe
                    src="{{ $src }}"
                    class="w-full h-full rounded-lg"
                    allowfullscreen
                ></iframe>
            </div>
        @else
            <div class="mb-4 aspect-video bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        @endif
        @break

    @case('columns')
        @php
            $columnCount = $properties['columns'] ?? 2;
            $columnBlocks = $properties['blocks'] ?? [];
        @endphp
        <div class="mb-4 grid gap-4 {{ $columnCount == 2 ? 'grid-cols-2' : ($columnCount == 3 ? 'grid-cols-3' : 'grid-cols-1') }}">
            @foreach($columnBlocks as $columnBlock)
                <div>
                    @include('livewire.admin.visual-editor.partials.block-preview-item', ['block' => $columnBlock])
                </div>
            @endforeach
        </div>
        @break

    @case('button')
        @php
            $url = $properties['url'] ?? '#';
            $variant = $properties['variant'] ?? 'primary';
            $alignment = $properties['alignment'] ?? 'left';
            $buttonClasses = match($variant) {
                'secondary' => 'bg-gray-200 text-gray-800 hover:bg-gray-300',
                'outline' => 'border-2 border-gray-800 text-gray-800 hover:bg-gray-800 hover:text-white',
                default => 'bg-blue-600 text-white hover:bg-blue-700'
            };
        @endphp
        <div class="mb-4 text-{{ $alignment }}">
            <a href="{{ $url }}" class="inline-block px-6 py-2 rounded-lg font-medium transition {{ $buttonClasses }}">
                {{ $content }}
            </a>
        </div>
        @break

    @case('html')
        <div class="mb-4">
            {!! $content !!}
        </div>
        @break

    @default
        {{-- Unknown block type - render as text --}}
        <div class="mb-4 p-4 bg-gray-100 rounded-lg text-gray-600">
            {{ $content }}
        </div>
@endswitch
