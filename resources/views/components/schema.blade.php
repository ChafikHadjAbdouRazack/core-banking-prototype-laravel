@props(['type' => 'organization', 'data' => []])

@php
use App\Helpers\SchemaHelper;

$schema = match($type) {
    'organization' => SchemaHelper::organization(),
    'website' => SchemaHelper::website(),
    'software' => SchemaHelper::softwareApplication(),
    'gcu' => SchemaHelper::gcuProduct(),
    'faq' => SchemaHelper::faq($data),
    'breadcrumb' => SchemaHelper::breadcrumb($data),
    'service' => SchemaHelper::service($data['name'] ?? '', $data['description'] ?? '', $data['category'] ?? ''),
    'article' => SchemaHelper::article($data),
    default => ''
};
@endphp

{!! $schema !!}