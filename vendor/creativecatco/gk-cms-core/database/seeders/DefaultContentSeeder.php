<?php

namespace CreativeCatCo\GkCmsCore\Database\Seeders;

use Illuminate\Database\Seeder;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Menu;
use CreativeCatCo\GkCmsCore\Models\Setting;

class DefaultContentSeeder extends Seeder
{
    /**
     * Seed the default pages, menus, and settings for a fresh GKeys CMS install.
     * This is idempotent — it won't create duplicates if pages already exist.
     */
    public function run(): void
    {
        $this->createDefaultPages();
        $this->createDefaultMenus();
        $this->createDefaultSettings();
    }

    protected function createDefaultPages(): void
    {
        $pages = [
            // Header (page_type: header)
            [
                'title' => 'Site Header',
                'slug' => '_header',
                'template' => 'default-header',
                'page_type' => 'header',
                'status' => 'published',
                'fields' => [
                    'logo_text' => 'My Website',
                    'cta_text' => 'Get Started',
                    'cta_url' => '/contact',
                ],
            ],
            // Footer (page_type: footer)
            [
                'title' => 'Site Footer',
                'slug' => '_footer',
                'template' => 'default-footer',
                'page_type' => 'footer',
                'status' => 'published',
                'fields' => [
                    'footer_text' => 'My Website',
                    'footer_tagline' => 'Building digital experiences that drive growth.',
                    'footer_email' => 'hello@example.com',
                    'footer_phone' => '(555) 123-4567',
                ],
            ],
            // Home Page
            [
                'title' => 'Home',
                'slug' => 'home',
                'template' => 'default-home',
                'page_type' => 'page',
                'status' => 'published',
                'is_homepage' => true,
                'fields' => [
                    'hero_heading' => 'Grow Your Business Online',
                    'hero_subheading' => 'We build beautiful, fast websites that convert visitors into customers. Let us help you unlock your digital potential.',
                    'hero_cta' => 'Get Started Today',
                    'hero_cta_url' => '/contact',
                    'services_heading' => 'What We Do',
                    'services_items' => [
                        ['title' => 'Web Design', 'desc' => 'Custom websites built for performance and conversions.', 'icon' => ''],
                        ['title' => 'Digital Marketing', 'desc' => 'Data-driven marketing strategies that deliver results.', 'icon' => ''],
                        ['title' => 'Brand Strategy', 'desc' => 'Build a brand identity that resonates with your audience.', 'icon' => ''],
                    ],
                    'about_heading' => 'Why Choose Us',
                    'about_text' => 'We combine creativity with technology to deliver digital experiences that drive real business growth.',
                    'cta_heading' => 'Ready to Get Started?',
                    'cta_text' => 'Contact us today for a free consultation.',
                    'cta_button' => 'Contact Us',
                ],
            ],
            // About Page
            [
                'title' => 'About',
                'slug' => 'about',
                'template' => 'default-about',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'About Us',
                    'page_subheading' => 'Learn more about our story, mission, and the team behind the work.',
                    'story_heading' => 'Our Story',
                    'story_text' => 'Founded with a passion for helping businesses grow online, we have been delivering exceptional digital experiences since day one.',
                    'mission_heading' => 'Our Mission',
                    'mission_text' => 'To empower businesses with innovative digital solutions that drive growth, build brand authority, and create meaningful connections.',
                    'team_heading' => 'Meet the Team',
                    'team_members' => [
                        ['name' => 'Jane Smith', 'role' => 'CEO & Founder', 'image' => '', 'bio' => ''],
                        ['name' => 'John Doe', 'role' => 'Lead Developer', 'image' => '', 'bio' => ''],
                        ['name' => 'Sarah Johnson', 'role' => 'Creative Director', 'image' => '', 'bio' => ''],
                    ],
                ],
            ],
            // Services Page
            [
                'title' => 'Services',
                'slug' => 'services',
                'template' => 'default-services',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'Our Services',
                    'page_subheading' => 'Comprehensive digital solutions to help your business thrive online.',
                    'services_items' => [
                        ['title' => 'Web Design', 'desc' => 'Custom, responsive websites designed to convert visitors into customers.', 'icon' => ''],
                        ['title' => 'SEO Optimization', 'desc' => 'Boost your search rankings and drive organic traffic to your site.', 'icon' => ''],
                        ['title' => 'Content Marketing', 'desc' => 'Engaging content strategies that build authority and attract leads.', 'icon' => ''],
                        ['title' => 'Social Media', 'desc' => 'Strategic social media management to grow your online presence.', 'icon' => ''],
                        ['title' => 'E-Commerce', 'desc' => 'Online stores built for seamless shopping experiences and growth.', 'icon' => ''],
                        ['title' => 'Brand Strategy', 'desc' => 'Build a cohesive brand identity that resonates with your audience.', 'icon' => ''],
                    ],
                    'cta_heading' => 'Ready to Transform Your Business?',
                    'cta_text' => 'Let\'s discuss how our services can help you achieve your goals.',
                    'cta_button' => 'Get a Free Quote',
                ],
            ],
            // Contact Page
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'template' => 'default-contact',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'Contact Us',
                    'page_subheading' => 'We\'d love to hear from you. Get in touch and let\'s discuss your project.',
                    'address' => '123 Main Street, Suite 100, City, State 12345',
                    'email' => 'hello@example.com',
                    'phone' => '(555) 123-4567',
                    'hours' => 'Monday - Friday: 9am - 5pm | Saturday - Sunday: Closed',
                ],
            ],
            // Blog Page
            [
                'title' => 'Blog',
                'slug' => 'blog',
                'template' => 'default-blog',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'Blog',
                    'page_subheading' => 'Insights, tips, and stories to help you grow your business.',
                ],
            ],
            // Portfolio Page
            [
                'title' => 'Portfolio',
                'slug' => 'portfolio',
                'template' => 'default-portfolio',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'Our Portfolio',
                    'page_subheading' => 'A showcase of our recent work and the results we\'ve delivered.',
                    'projects' => [
                        ['title' => 'E-Commerce Redesign', 'desc' => 'Complete redesign resulting in 40% increase in conversions.', 'category' => 'Web Design', 'image' => ''],
                        ['title' => 'Brand Identity', 'desc' => 'Full brand identity package including logo and style guide.', 'category' => 'Branding', 'image' => ''],
                        ['title' => 'Marketing Campaign', 'desc' => 'Multi-channel campaign that generated 200% ROI.', 'category' => 'Marketing', 'image' => ''],
                        ['title' => 'Mobile App', 'desc' => 'Cross-platform mobile application with 50K+ downloads.', 'category' => 'Development', 'image' => ''],
                        ['title' => 'SEO Strategy', 'desc' => 'SEO overhaul increasing organic traffic by 300%.', 'category' => 'SEO', 'image' => ''],
                        ['title' => 'Corporate Website', 'desc' => 'Modern corporate website with CMS and analytics.', 'category' => 'Web Design', 'image' => ''],
                    ],
                ],
            ],
            // Products Page
            [
                'title' => 'Products',
                'slug' => 'products',
                'template' => 'default-products',
                'page_type' => 'page',
                'status' => 'published',
                'fields' => [
                    'page_heading' => 'Our Products',
                    'page_subheading' => 'Premium tools and solutions designed to accelerate your growth.',
                    'products' => [
                        ['name' => 'Starter Package', 'desc' => 'Perfect for small businesses getting started online.', 'price' => '$499', 'image' => ''],
                        ['name' => 'Growth Package', 'desc' => 'For businesses ready to scale.', 'price' => '$999', 'image' => ''],
                        ['name' => 'Enterprise Package', 'desc' => 'Full-service digital solution for established businesses.', 'price' => '$2,499', 'image' => ''],
                        ['name' => 'SEO Toolkit', 'desc' => 'Comprehensive SEO audit and optimization roadmap.', 'price' => '$299', 'image' => ''],
                        ['name' => 'Brand Kit', 'desc' => 'Complete brand identity package.', 'price' => '$799', 'image' => ''],
                        ['name' => 'Maintenance Plan', 'desc' => 'Monthly website maintenance and support.', 'price' => '$149/mo', 'image' => ''],
                    ],
                ],
            ],
            // Sample Blog Post
            [
                'title' => '10 Tips to Grow Your Business Online',
                'slug' => '10-tips-grow-business-online',
                'template' => 'default-post',
                'page_type' => 'post',
                'status' => 'published',
                'fields' => [
                    'post_title' => '10 Tips to Grow Your Business Online',
                    'excerpt' => 'Discover proven strategies to expand your digital presence and attract more customers.',
                    'category' => 'Business Growth',
                    'author' => 'Admin',
                    'content' => '<h2>1. Build a Professional Website</h2><p>Your website is your digital storefront. Make sure it\'s fast, mobile-friendly, and designed to convert visitors into customers.</p><h2>2. Invest in SEO</h2><p>Search engine optimization helps potential customers find you organically. Focus on quality content, technical SEO, and building authority.</p><h2>3. Leverage Social Media</h2><p>Choose the platforms where your audience spends time and create engaging content that builds community and drives traffic.</p><h2>4. Create Valuable Content</h2><p>Content marketing establishes your expertise and attracts leads. Blog posts, videos, and guides all contribute to your online authority.</p><h2>5. Use Email Marketing</h2><p>Build an email list and nurture relationships with your audience through valuable, consistent communication.</p><h2>6. Optimize for Mobile</h2><p>More than half of web traffic comes from mobile devices. Ensure your entire digital experience is mobile-optimized.</p><h2>7. Track Your Analytics</h2><p>Use data to understand what\'s working and what isn\'t. Make informed decisions based on real metrics.</p><h2>8. Build Trust with Reviews</h2><p>Encourage satisfied customers to leave reviews. Social proof is one of the most powerful conversion tools.</p><h2>9. Run Targeted Ads</h2><p>Paid advertising can accelerate growth when done strategically. Start small, test, and scale what works.</p><h2>10. Stay Consistent</h2><p>Growth takes time. Stay consistent with your efforts and the results will compound over time.</p>',
                ],
            ],
        ];

        foreach ($pages as $pageData) {
            // Skip if page with this slug already exists
            if (Page::where('slug', $pageData['slug'])->exists()) {
                continue;
            }

            $isHomepage = $pageData['is_homepage'] ?? false;
            unset($pageData['is_homepage']);

            $page = Page::create($pageData);

            // Set homepage
            if ($isHomepage) {
                Setting::set('homepage_id', $page->id);
            }
        }
    }

    protected function createDefaultMenus(): void
    {
        // Header Menu
        if (!Menu::where('location', 'header')->exists()) {
            Menu::create([
                'name' => 'Main Navigation',
                'location' => 'header',
                'is_active' => true,
                'items' => [
                    ['label' => 'Home', 'url' => '/', 'type' => 'page', 'target' => '_self'],
                    ['label' => 'About', 'url' => '/about', 'type' => 'page', 'target' => '_self'],
                    [
                        'label' => 'Services',
                        'url' => '/services',
                        'type' => 'page',
                        'target' => '_self',
                        'children' => [
                            ['label' => 'All Services', 'url' => '/services', 'type' => 'page', 'target' => '_self'],
                            ['label' => 'SEO Services', 'url' => '/seo-services', 'type' => 'page', 'target' => '_self'],
                            ['label' => 'Web Development', 'url' => '/website-development', 'type' => 'page', 'target' => '_self'],
                        ],
                    ],
                    ['label' => 'Portfolio', 'url' => '/portfolio', 'type' => 'page', 'target' => '_self'],
                    ['label' => 'Products', 'url' => '/products', 'type' => 'page', 'target' => '_self'],
                    ['label' => 'Blog', 'url' => '/blog', 'type' => 'page', 'target' => '_self'],
                    ['label' => 'Contact', 'url' => '/contact', 'type' => 'page', 'target' => '_self'],
                ],
            ]);
        }

        // Footer Menu
        if (!Menu::where('location', 'footer')->exists()) {
            Menu::create([
                'name' => 'Footer Navigation',
                'location' => 'footer',
                'is_active' => true,
                'items' => [
                    ['label' => 'Home', 'url' => '/', 'type' => 'page'],
                    ['label' => 'About', 'url' => '/about', 'type' => 'page'],
                    ['label' => 'Services', 'url' => '/services', 'type' => 'page'],
                    ['label' => 'Contact', 'url' => '/contact', 'type' => 'page'],
                    ['label' => 'Privacy Policy', 'url' => '/privacy', 'type' => 'custom'],
                ],
            ]);
        }
    }

    protected function createDefaultSettings(): void
    {
        $defaults = [
            'company_name' => 'My Website',
            'company_email' => 'hello@example.com',
            'company_phone' => '(555) 123-4567',
            'company_address' => '123 Main Street, City, State 12345',
            'theme_primary_color' => '#cfff2e',
            'theme_secondary_color' => '#293726',
            'theme_accent_color' => '#3b82f6',
            'theme_text_color' => '#1a1a2e',
            'theme_bg_color' => '#ffffff',
            'theme_font_heading' => 'Inter',
            'theme_font_body' => 'Inter',
            'admin_email' => 'admin@example.com',
            'email_from_name' => 'My Website',
            'email_from_address' => 'noreply@example.com',
            'smtp_enabled' => '0',
            'onboarding_complete' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::set($key, $value);
            }
        }
    }
}
