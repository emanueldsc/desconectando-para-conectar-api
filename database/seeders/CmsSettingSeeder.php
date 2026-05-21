<?php

namespace Database\Seeders;

use App\Models\CmsSetting;
use Illuminate\Database\Seeder;

class CmsSettingSeeder extends Seeder
{
    public function run(): void
    {
        CmsSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'banners' => [
                    [
                        'url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC1zkLvq2X2LAXXSCEJqTW_fRWDeAv6y-aDc45lt6WD1YpneUezJn1iDskm8FZ4c5oaDQUw5K53u7tLcrmW9OB-T2Hm4khNh8sKlOwlWe1gINGA-o0qgj57Vg66HA3mPW54bI89xlJ8J9PscMPcGCIrY6D4sOQ_dKjjCrNISmHKUY1S5aXlY-TPmiWNTQoOJkPlcOGo-k9X6M_Nj3u0alWupFXu2kSoexkaiIgq7B3GedzmFWwt0--jebsSGsVclm3tHdn9Bgb9gQmP',
                        'alt' => 'Banner Atual',
                        'label' => 'Banner Principal',
                    ],
                ],
                'phrases' => [
                    'A união faz a força do nosso povo.',
                    'Conectando corações e transformando vidas.',
                ],
                'contact' => [
                    'email' => 'contato@desconectando.com.br',
                    'whatsapp' => '(81) 99999-0000',
                    'phone' => '(81) 3333-4444',
                ],
                'socials' => [
                    'instagram' => 'https://instagram.com/desconectando',
                    'facebook' => 'https://facebook.com/desconectando',
                    'youtube' => '',
                ],
                'hero_button' => [
                    'label' => 'Participar Agora',
                    'link' => '/public/raffles',
                    'icon' => 'favorite_border',
                    'backgroundColor' => '#d35400',
                    'textColor' => '#ffffff',
                ],
                'home_reality' => [
                    'title' => 'Nossa Realidade',
                    'subtitle' => 'Publicações em destaque sobre a transformação da nossa comunidade.',
                    'displayMode' => 'latest',
                    'publicationIds' => [],
                ],
            ]
        );
    }
}
