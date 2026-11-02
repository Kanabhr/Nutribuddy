<?php

namespace App\Services\Ecommerce;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductContentService
{

    public function storeProductCardImages(Request $request, Product $product): void
    {
        $updates = [];

        if ($request->hasFile('card_image')) {
            if ($product->card_image_path) {
                Storage::disk('public')->delete($product->card_image_path);
            }

            $updates['card_image_path'] = $request->file('card_image')->store('product-cards', 'public');
        }

        if ($request->hasFile('card_hover_image')) {
            if ($product->card_hover_image_path) {
                Storage::disk('public')->delete($product->card_hover_image_path);
            }

            $updates['card_hover_image_path'] = $request->file('card_hover_image')->store('product-cards', 'public');
        }

        if ($updates) {
            $product->forceFill($updates)->save();
        }
    }


    public function prepareProductTags(Request $request): array
    {
        $tags = $request->input('tags', []);
        $tags = is_array($tags) ? $tags : [];

        if ($request->hasFile('tag_images')) {
            foreach ($request->file('tag_images') as $index => $file) {
                if (! $file) {
                    continue;
                }

                if (! isset($tags[$index]) || ! is_array($tags[$index])) {
                    $tags[$index] = [];
                }

                $tags[$index]['icon'] = $file->store('tags', 'public');
            }
        }

        return collect($tags)
            ->filter(fn ($tag) => is_array($tag) && trim((string) ($tag['text'] ?? '')) !== '')
            ->map(fn ($tag) => [
                'icon' => trim((string) ($tag['icon'] ?? '')),
                'text' => trim((string) ($tag['text'] ?? '')),
            ])
            ->values()
            ->all();
    }


    private function transformSectionValidationRules(): array
    {
        return [
            'transform_description' => ['nullable', 'string', 'max:1000'],
            'transform_main_image' => ['nullable', 'string', 'max:255'],
            'transform_main_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'transform_results' => ['nullable', 'array'],
            'transform_results.*.image' => ['nullable', 'string', 'max:255'],
            'transform_results.*.title' => ['nullable', 'string', 'max:150'],
            'transform_results.*.description' => ['nullable', 'string', 'max:1000'],
            'transform_results.*.week' => ['nullable', 'string', 'max:80'],
            'transform_result_images' => ['nullable', 'array'],
            'transform_result_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'benefits_cards' => ['nullable', 'array'],
            'benefits_cards.*.title' => ['nullable', 'string', 'max:150'],
            'benefits_cards.*.description' => ['nullable', 'string', 'max:1000'],
            'benefits_card_images' => ['nullable', 'array'],
            'benefits_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'problem_heading' => ['nullable', 'string', 'max:255'],
            'problem_title' => ['nullable', 'string', 'max:500'],
            'problem_subtitle' => ['nullable', 'string', 'max:1000'],
            'problem_cards' => ['nullable', 'array'],
            'problem_cards.*.title' => ['nullable', 'string', 'max:150'],
            'problem_cards.*.description' => ['nullable', 'string', 'max:1000'],
            'problem_card_images' => ['nullable', 'array'],
            'problem_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }


    public function unsetTransformSectionFields(array &$validated): void
    {
        foreach (array_keys($this->transformSectionValidationRules()) as $field) {
            unset($validated[$field]);
        }
    }


    public function saveTransformSectionContent(Request $request, Product $product): void
    {
        $mainImage = trim((string) $request->input('transform_main_image', '')) ?: null;

        if ($request->hasFile('transform_main_image_file')) {
            $this->deleteTransformImage($product->transform_main_image);
            $mainImage = $request->file('transform_main_image_file')->store('product-transforms', 'public');
        }

        $results = $request->input('transform_results', []);
        $results = is_array($results) ? $results : [];

        if ($request->hasFile('transform_result_images')) {
            foreach ($request->file('transform_result_images') as $index => $file) {
                if (! $file) {
                    continue;
                }

                if (! isset($results[$index]) || ! is_array($results[$index])) {
                    $results[$index] = [];
                }

                $results[$index]['image'] = $file->store('product-transforms/results', 'public');
            }
        }

        $results = collect($results)
            ->filter(fn ($result) => is_array($result) && (
                trim((string) ($result['image'] ?? '')) !== '' ||
                trim((string) ($result['title'] ?? '')) !== '' ||
                trim((string) ($result['description'] ?? '')) !== '' ||
                trim((string) ($result['week'] ?? '')) !== ''
            ))
            ->map(fn ($result) => [
                'image' => trim((string) ($result['image'] ?? '')),
                'title' => trim((string) ($result['title'] ?? '')),
                'description' => trim((string) ($result['description'] ?? '')),
                'week' => trim((string) ($result['week'] ?? '')),
            ])
            ->values()
            ->all();

        $keptImages = collect($results)->pluck('image')->filter()->all();
        collect($product->transform_results ?? [])
            ->pluck('image')
            ->filter(fn ($path) => $path && ! in_array($path, $keptImages, true))
            ->each(fn ($path) => $this->deleteTransformImage($path));

        $product->forceFill([
            'transform_description' => trim((string) $request->input('transform_description', '')) ?: null,
            'transform_main_image' => $mainImage,
            'transform_results' => $results,
        ])->save();
    }


    public function saveBenefitsCardsContent(Request $request, Product $product): void
    {
        $cards = $request->input('benefits_cards', []);
        $cards = is_array($cards) ? $cards : [];
        $deletions = $request->input('delete_benefits_card_icon', []);

        foreach ($deletions as $index => $shouldDelete) {
            if ($shouldDelete == '1' && isset($cards[$index])) {
                $cards[$index]['icon'] = '';
            }
        }

        if ($request->hasFile('benefits_card_images')) {
            foreach ($request->file('benefits_card_images') as $index => $file) {
                if (! $file) {
                    continue;
                }

                if (! isset($cards[$index]) || ! is_array($cards[$index])) {
                    $cards[$index] = [];
                }

                $cards[$index]['icon'] = $file->store('product-benefits', 'public');
            }
        }

        $cards = collect($cards)
            ->filter(fn ($card) => is_array($card) && (
                trim((string) ($card['icon'] ?? '')) !== '' ||
                trim((string) ($card['title'] ?? '')) !== '' ||
                trim((string) ($card['description'] ?? '')) !== ''
            ))
            ->map(fn ($card) => [
                'icon' => trim((string) ($card['icon'] ?? '')),
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
            ])
            ->values()
            ->all();

        $keptImages = collect($cards)->pluck('icon')->filter()->all();
        collect($product->benefits_cards ?? [])
            ->pluck('icon')
            ->filter(fn ($path) => $path && ! in_array($path, $keptImages, true))
            ->each(fn ($path) => $this->deleteTransformImage($path));

        $product->forceFill([
            'benefits_cards' => $cards,
        ])->save();
    }


    public function saveProblemSectionContent(Request $request, Product $product): void
    {
        $cards = $request->input('problem_cards', []);
        $cards = is_array($cards) ? $cards : [];
        $deletions = $request->input('delete_problem_card_icon', []);

        foreach ($deletions as $index => $shouldDelete) {
            if ($shouldDelete == '1' && isset($cards[$index])) {
                $cards[$index]['icon'] = '';
            }
        }

        if ($request->hasFile('problem_card_images')) {
            foreach ($request->file('problem_card_images') as $index => $file) {
                if (! $file) {
                    continue;
                }

                if (! isset($cards[$index]) || ! is_array($cards[$index])) {
                    $cards[$index] = [];
                }

                $cards[$index]['icon'] = $file->store('product-problems', 'public');
            }
        }

        $cards = collect($cards)
            ->filter(fn ($card) => is_array($card) && (
                trim((string) ($card['icon'] ?? '')) !== '' ||
                trim((string) ($card['title'] ?? '')) !== '' ||
                trim((string) ($card['description'] ?? '')) !== ''
            ))
            ->map(fn ($card) => [
                'icon' => trim((string) ($card['icon'] ?? '')),
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
            ])
            ->values()
            ->all();

        $keptImages = collect($cards)->pluck('icon')->filter()->all();
        collect($product->problem_cards ?? [])
            ->pluck('icon')
            ->filter(fn ($path) => $path && ! in_array($path, $keptImages, true))
            ->each(fn ($path) => $this->deleteTransformImage($path));

        $product->forceFill([
            'problem_heading' => $request->input('problem_heading'),
            'problem_title' => $request->input('problem_title'),
            'problem_subtitle' => $request->input('problem_subtitle'),
            'problem_cards' => $cards,
        ])->save();
    }


    public function deleteTransformImage(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['img/', 'assets/'])) {
            Storage::disk('public')->delete($path);
        }
    }


    private function problemSolutionValidationRules(): array
    {
        return [
            'ps_brand_title' => ['nullable', 'string', 'max:255'],
            'ps_product_title' => ['nullable', 'string', 'max:255'],
            'ps_tagline_items' => ['nullable', 'array'],
            'ps_tagline_items.*' => ['nullable', 'string', 'max:120'],
            'ps_left_label' => ['nullable', 'string', 'max:255'],
            'ps_left_cards' => ['nullable', 'array'],
            'ps_left_cards.*.title' => ['nullable', 'string', 'max:120'],
            'ps_left_cards.*.text' => ['nullable', 'string', 'max:255'],
            'ps_left_cards.*.icon' => ['nullable', 'string', 'max:255'],
            'ps_left_card_images' => ['nullable', 'array'],
            'ps_left_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_center_image' => ['nullable', 'string', 'max:255'],
            'ps_center_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'ps_right_label' => ['nullable', 'string', 'max:255'],
            'ps_right_cards' => ['nullable', 'array'],
            'ps_right_cards.*.title' => ['nullable', 'string', 'max:120'],
            'ps_right_cards.*.text' => ['nullable', 'string', 'max:255'],
            'ps_right_cards.*.icon' => ['nullable', 'string', 'max:255'],
            'ps_right_card_images' => ['nullable', 'array'],
            'ps_right_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_shelf_left_image' => ['nullable', 'string', 'max:255'],
            'ps_shelf_left_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_shelf_right_image' => ['nullable', 'string', 'max:255'],
            'ps_shelf_right_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }


    public function unsetProblemSolutionFields(array &$validated): void
    {
        foreach (array_keys($this->problemSolutionValidationRules()) as $field) {
            unset($validated[$field]);
        }
    }


    public function saveProblemSolutionContent(Request $request, Product $product): void
    {
        $updates = [
            'ps_brand_title' => trim((string) $request->input('ps_brand_title', '')) ?: null,
            'ps_product_title' => trim((string) $request->input('ps_product_title', '')) ?: null,
            'ps_tagline_items' => $this->prepareProblemSolutionTagline($request->input('ps_tagline_items', [])),
            'ps_left_label' => trim((string) $request->input('ps_left_label', '')) ?: null,
            'ps_left_cards' => $this->prepareProblemSolutionCards($request, 'ps_left_cards', 'ps_left_card_images'),
            'ps_center_image' => $request->input('ps_center_image'),
            'ps_right_label' => trim((string) $request->input('ps_right_label', '')) ?: null,
            'ps_right_cards' => $this->prepareProblemSolutionCards($request, 'ps_right_cards', 'ps_right_card_images'),
            'ps_shelf_left_image' => $request->input('ps_shelf_left_image'),
            'ps_shelf_right_image' => $request->input('ps_shelf_right_image'),
        ];

        $this->replaceProblemSolutionImage($request, $product, $updates, 'ps_center_image', 'ps_center_image_file');
        $this->replaceProblemSolutionImage($request, $product, $updates, 'ps_shelf_left_image', 'ps_shelf_left_image_file');
        $this->replaceProblemSolutionImage($request, $product, $updates, 'ps_shelf_right_image', 'ps_shelf_right_image_file');

        $product->forceFill($updates)->save();
    }


    public function prepareProblemSolutionTagline(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }


    public function prepareProblemSolutionCards(Request $request, string $field, string $fileField): array
    {
        $cards = $request->input($field, []);
        $cards = is_array($cards) ? $cards : [];

        if ($request->hasFile($fileField)) {
            foreach ($request->file($fileField) as $index => $file) {
                if (! $file) {
                    continue;
                }

                if (! isset($cards[$index]) || ! is_array($cards[$index])) {
                    $cards[$index] = [];
                }

                $cards[$index]['icon'] = $file->store('problem-solution', 'public');
            }
        }

        return collect($cards)
            ->filter(fn ($card) => is_array($card) && (
                trim((string) ($card['title'] ?? '')) !== '' ||
                trim((string) ($card['text'] ?? '')) !== '' ||
                trim((string) ($card['icon'] ?? '')) !== ''
            ))
            ->map(fn ($card) => [
                'icon' => trim((string) ($card['icon'] ?? '')),
                'title' => trim((string) ($card['title'] ?? '')),
                'text' => trim((string) ($card['text'] ?? '')),
            ])
            ->values()
            ->all();
    }


    public function replaceProblemSolutionImage(Request $request, Product $product, array &$updates, string $column, string $fileField): void
    {
        if (! $request->hasFile($fileField)) {
            $updates[$column] = trim((string) ($updates[$column] ?? '')) ?: null;
            return;
        }

        if ($product->{$column}) {
            Storage::disk('public')->delete($product->{$column});
        }

        $updates[$column] = $request->file($fileField)->store('problem-solution', 'public');
    }


    public function defaultProblemSolutionContent(): array
    {
        return [
            'brand_title' => 'Nutribuddy',
            'product_title' => 'Immunity Booster Gummies',
            'tagline_items' => ['Daily Nutrition', 'Stronger Immunity', 'Healthier You'],
            'left_label' => "Power Of\nNature",
            'left_cards' => [
                ['icon' => 'img/haldi.webp', 'title' => 'TURMERIC', 'text' => "Fights germs &\nsupports immunity"],
                ['icon' => 'img/Amla.webp', 'title' => 'AMLA', 'text' => "Rich in Vitamin C,\nstrengthens body defenses"],
                ['icon' => 'img/adrak.png', 'title' => 'GINGER', 'text' => "Soothes throat &\nhelps fight infections"],
            ],
            'center_image' => 'img/product2.png',
            'right_label' => "Daily Goodness\nIn Every Gummy!",
            'right_cards' => [
                ['icon' => 'img/new-btn-2.png', 'title' => 'VITAMINS & MINERALS', 'text' => 'Daily nutrition to build strong immunity'],
                ['icon' => 'img/bb1.png', 'title' => 'NATURAL & SAFE', 'text' => 'Made with natural ingredients'],
                ['icon' => 'img/c4.png', 'title' => 'YUMMY & FUN', 'text' => 'Delicious gummies kids will love'],
                ['icon' => 'img/new-btn-3.png', 'title' => 'MODERN SCIENCE', 'text' => 'Formulated with care and research'],
            ],
            'shelf_left_image' => 'img/Amla.webp',
            'shelf_right_image' => 'img/haldi.webp',
        ];
    }
}
