{{--
    GKeys CMS - Default About Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)
    - story_heading (text), story_text (textarea), story_image (image)
    - mission_heading (text), mission_text (textarea)
    - team_heading (text)
    - team_members (repeater): name, role, image, bio
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'About Us' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'Learn more about our story, mission, and the team behind the work.' }}</p>
    </div>
</section>

{{-- Our Story --}}
<section class="py-20" data-section-bg="story_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6" data-field="story_heading" data-field-type="text">{{ $fields['story_heading'] ?? 'Our Story' }}</h2>
                <p class="text-gray-600 text-lg leading-relaxed" data-field="story_text" data-field-type="textarea">{{ $fields['story_text'] ?? 'Founded with a passion for helping businesses grow online, we have been delivering exceptional digital experiences since day one. Our journey started with a simple belief: every business deserves a powerful online presence that drives real results.' }}</p>
            </div>
            <div>
                <img src="{{ $fields['story_image'] ?? 'https://placehold.co/600x400/e2e8f0/94a3b8?text=Our+Story' }}" alt="Our story" class="rounded-xl shadow-lg w-full" data-field="story_image" data-field-type="image">
            </div>
        </div>
    </div>
</section>

{{-- Mission --}}
<section class="py-20 bg-gray-50" data-section-bg="mission_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6" data-field="mission_heading" data-field-type="text">{{ $fields['mission_heading'] ?? 'Our Mission' }}</h2>
        <p class="text-gray-600 text-lg leading-relaxed" data-field="mission_text" data-field-type="textarea">{{ $fields['mission_text'] ?? 'To empower businesses with innovative digital solutions that drive growth, build brand authority, and create meaningful connections with their audience. We believe in the power of great design, smart strategy, and cutting-edge technology.' }}</p>
    </div>
</section>

{{-- Team --}}
<section class="py-20" data-section-bg="team_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12" data-field="team_heading" data-field-type="text">{{ $fields['team_heading'] ?? 'Meet the Team' }}</h2>
        @php
            $teamMembers = $fields['team_members'] ?? [
                ['name' => 'Jane Smith', 'role' => 'CEO & Founder', 'image' => '', 'bio' => ''],
                ['name' => 'John Doe', 'role' => 'Lead Developer', 'image' => '', 'bio' => ''],
                ['name' => 'Sarah Johnson', 'role' => 'Creative Director', 'image' => '', 'bio' => ''],
            ];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8" data-field="team_members" data-field-type="repeater"
             data-repeater-fields='[{"key":"name","type":"text","label":"Name"},{"key":"role","type":"text","label":"Role"},{"key":"image","type":"image","label":"Photo"},{"key":"bio","type":"textarea","label":"Bio"}]'>
            @foreach($teamMembers as $index => $member)
                <div class="text-center" data-repeater-item="{{ $index }}">
                    <img src="{{ $member['image'] ?? 'https://placehold.co/300x300/e2e8f0/94a3b8?text=' . urlencode($member['name'] ?? 'Team') }}" alt="{{ $member['name'] ?? 'Team member' }}" class="w-48 h-48 rounded-full mx-auto mb-4 object-cover shadow-lg" data-field="team_members.{{ $index }}.image" data-field-type="image">
                    <h3 class="text-xl font-semibold" data-field="team_members.{{ $index }}.name" data-field-type="text">{{ $member['name'] ?? 'Team Member' }}</h3>
                    <p class="text-gray-500" data-field="team_members.{{ $index }}.role" data-field-type="text">{{ $member['role'] ?? 'Role' }}</p>
                    @if(!empty($member['bio']))
                        <p class="text-gray-600 text-sm mt-2" data-field="team_members.{{ $index }}.bio" data-field-type="textarea">{{ $member['bio'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
