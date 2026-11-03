<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'blog_category_id',
        'author_id',
        'author_name',
        'title',
        'slug',
        'short_description',
        'content',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function getDisplayAuthorAttribute(): string
    {
        return !empty($this->author_name) ? $this->author_name : ($this->author?->name ?? 'NutriBuddy Team');
    }

    public function getTagsListAttribute(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->tags))));
    }

    public function getExcerptAttribute(): string
    {
        $rawText = !empty($this->short_description)
            ? $this->short_description
            : ($this->content ?? '');

        $clean = strip_tags($rawText);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        return \Illuminate\Support\Str::words($clean, 100, '...');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
