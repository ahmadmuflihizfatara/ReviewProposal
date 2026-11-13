@php
    use App\Models\Review;

    $aspects = Review::ASPECTS;
    $totalValue = $review->totalValue();
@endphp
<div class="card mb-6">
    <div class="flex flex-wrap items-start justify-between gap-6">
        <div>
            <p class="eyebrow">Reviewer</p>
            <p class="mt-1 text-xl">{{ $review->reviewer->name }}</p>
        </div>
        @if ($totalValue !== null)
            <div class="text-right">
                <p class="eyebrow">Total nilai</p>
                <p class="mt-1 font-sans font-bold text-3xl tracking-tight">{{ Review::formatNumber($totalValue) }}</p>
            </div>
        @endif
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left min-w-[560px]">
            <thead class="font-sans text-xs uppercase tracking-wider text-cloud-dark">
                <tr class="border-b border-stone">
                    <th class="py-3 pr-4 font-semibold">Aspek</th>
                    <th class="py-3 pr-4 font-semibold">Bobot</th>
                    <th class="py-3 pr-4 font-semibold">Skor</th>
                    <th class="py-3 pr-4 font-semibold">Nilai</th>
                    <th class="py-3 font-semibold">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($aspects as $key => [$label, $weight])
                    <tr class="border-b border-stone align-top">
                        <td class="py-4 pr-4 font-sans text-sm font-medium">{{ $label }}</td>
                        <td class="py-4 pr-4 font-sans text-sm">{{ $weight }}%</td>
                        <td class="py-4 pr-4 font-sans text-sm">{{ $review->scores[$key] ?? '-' }}</td>
                        <td class="py-4 pr-4 font-sans text-sm">{{ Review::formatNumber($review->values[$key] ?? null) }}</td>
                        <td class="py-4 text-base leading-relaxed whitespace-pre-line">{{ $review->notes[$key] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($review->recommendation_note)
        <div class="mt-8">
            <p class="eyebrow">Rekomendasi</p>
            <p class="mt-2 text-lg leading-relaxed whitespace-pre-line">{{ $review->recommendation_note }}</p>
        </div>
    @endif
</div>
