<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteSettings;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function about(PublicSiteSettings $settings): View
    {
        return view('pages.about', [
            'profile' => $settings->values(['profile_name', 'profile_description', 'profile_address']),
        ]);
    }

    public function contact(PublicSiteSettings $settings): View
    {
        $contact = $settings->values([
            'contact_address',
            'contact_email',
            'contact_phone',
            'contact_map_url',
            'profile_name',
            'whatsapp_number',
            'social_instagram',
            'social_facebook',
            'social_youtube',
        ]);

        return view('pages.contact', [
            'contact' => $contact,
            'whatsappAvailable' => $settings->whatsappNumber() !== null,
            'mapUrl' => $settings->safeMapUrl($contact['contact_map_url'] ?? null),
            'socialLinks' => array_filter([
                'Instagram' => $settings->safeExternalUrl($contact['social_instagram'] ?? null),
                'Facebook' => $settings->safeExternalUrl($contact['social_facebook'] ?? null),
                'YouTube' => $settings->safeExternalUrl($contact['social_youtube'] ?? null),
            ]),
        ]);
    }
}
