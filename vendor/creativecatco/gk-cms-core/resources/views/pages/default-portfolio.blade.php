{{--
    GKeys CMS - Default Portfolio Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)
    - projects (repeater): title, desc, image, category
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'Our Portfolio' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'A showcase of our recent work and the results we\'ve delivered.' }}</p>
    </div>
</section>

{{-- Portfolio Grid --}}
<section class="py-20" data-section-bg="portfolio_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $defaultProjects = [
                ['title' => 'E-Commerce Redesign', 'desc' => 'Complete redesign of an online store resulting in 40% increase in conversions.', 'category' => 'Web Design', 'image' => ''],
                ['title' => 'Brand Identity', 'desc' => 'Full brand identity package including logo, colors, and style guide.', 'category' => 'Branding', 'image' => ''],
                ['title' => 'Marketing Campaign', 'desc' => 'Multi-channel marketing campaign that generated 200% ROI.', 'category' => 'Marketing', 'image' => ''],
                ['title' => 'Mobile App', 'desc' => 'Cross-platform mobile application with 50K+ downloads.', 'category' => 'Development', 'image' => ''],
                ['title' => 'SEO Strategy', 'desc' => 'SEO overhaul that increased organic traffic by 300% in 6 months.', 'category' => 'SEO', 'image' => ''],
                ['title' => 'Corporate Website', 'desc' => 'Modern corporate website with CMS integration and analytics.', 'category' => 'Web Design', 'image' => ''],
            ];
            $projects = $fields['projects'] ?? $defaultProjects;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-field="projects" data-field-type="repeater"
             data-repeater-fields='[{"key":"title","type":"text","label":"Title"},{"key":"desc","type":"textarea","label":"Description"},{"key":"image","type":"image","label":"Image"},{"key":"category","type":"text","label":"Category"}]'>
            @foreach($projects as $index => $project)
                <div class="group rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow bg-white" data-repeater-item="{{ $index }}">
                    <div class="relative overflow-hidden aspect-video">
                        <img src="{{ $project['image'] ?? 'https://placehold.co/600x400/e2e8f0/94a3b8?text=Project+' . ($index + 1) }}"
                             alt="{{ $project['title'] ?? 'Project' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                             data-field="projects.{{ $index }}.image" data-field-type="image">
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full" style="background-color: var(--color-primary); color: var(--color-secondary)"
                                  data-field="projects.{{ $index }}.category" data-field-type="text">{{ $project['category'] ?? 'Category' }}</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-2" data-field="projects.{{ $index }}.title" data-field-type="text">{{ $project['title'] ?? 'Project Title' }}</h3>
                        <p class="text-gray-600 text-sm" data-field="projects.{{ $index }}.desc" data-field-type="textarea">{{ $project['desc'] ?? 'Project description.' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
