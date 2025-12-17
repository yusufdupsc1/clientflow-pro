@php
    $items = old('items', $invoice?->items?->map(function ($item) {
        return [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price_cents' => $item->unit_price_cents,
        ];
    })->toArray() ?? [['description' => '', 'quantity' => 1, 'unit_price_cents' => 0]]);
@endphp

@foreach ($items as $index => $item)
    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4 space-y-3 mb-3 bg-white/50 dark:bg-gray-900/40">
        <div>
            <x-input-label for="items_{{ $index }}_description" :value="__('Description')" />
            <x-text-input id="items_{{ $index }}_description" name="items[{{ $index }}][description]" type="text" class="mt-1 block w-full" :value="old('items.' . $index . '.description', $item['description'])" required />
            <x-input-error :messages="$errors->get('items.' . $index . '.description')" class="mt-2" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="items_{{ $index }}_quantity" :value="__('Quantity')" />
                <x-text-input id="items_{{ $index }}_quantity" name="items[{{ $index }}][quantity]" type="number" min="1" class="mt-1 block w-full" :value="old('items.' . $index . '.quantity', $item['quantity'])" required />
                <x-input-error :messages="$errors->get('items.' . $index . '.quantity')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="items_{{ $index }}_unit_price_cents" :value="__('Unit Price (cents)')" />
                <x-text-input id="items_{{ $index }}_unit_price_cents" name="items[{{ $index }}][unit_price_cents]" type="number" min="0" class="mt-1 block w-full" :value="old('items.' . $index . '.unit_price_cents', $item['unit_price_cents'])" required />
                <x-input-error :messages="$errors->get('items.' . $index . '.unit_price_cents')" class="mt-2" />
            </div>
        </div>
    </div>
@endforeach
