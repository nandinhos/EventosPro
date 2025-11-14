<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando artistas de música eletrônica brasileiros...');

        $artists = [
            // DJs Brasileiros Famosos
            [
                'name' => 'Alok',
                'contact_info' => 'contato@alokmusic.com | +55 11 99999-0001 | Instagram: @alok',
            ],
            [
                'name' => 'Vintage Culture',
                'contact_info' => 'booking@vintageculture.com | +55 11 99999-0002 | Instagram: @vintageculture',
            ],
            [
                'name' => 'KVSH',
                'contact_info' => 'contato@kvshmusic.com | +55 11 99999-0003 | Instagram: @kvsh',
            ],
            [
                'name' => 'Cat Dealers',
                'contact_info' => 'booking@catdealers.com | +55 11 99999-0004 | Instagram: @catdealers',
            ],
            [
                'name' => 'Felguk',
                'contact_info' => 'contato@felguk.com | +55 11 99999-0005 | Instagram: @felguk',
            ],
            [
                'name' => 'Gui Boratto',
                'contact_info' => 'booking@guiboratto.com | +55 11 99999-0006 | Instagram: @guiboratto',
            ],
            [
                'name' => 'Mau P',
                'contact_info' => 'contato@maupmusic.com | +55 11 99999-0007 | Instagram: @maup',
            ],
            [
                'name' => 'Illusionize',
                'contact_info' => 'booking@illusionize.com | +55 11 99999-0008 | Instagram: @illusionize',
            ],
            [
                'name' => 'Dubdogz',
                'contact_info' => 'contato@dubdogz.com | +55 11 99999-0009 | Instagram: @dubdogz',
            ],
            [
                'name' => 'Chemical Surf',
                'contact_info' => 'booking@chemicalsurf.com | +55 11 99999-0010 | Instagram: @chemicalsurf',
            ],

            // DJs Emergentes e Regionais
            [
                'name' => 'Bruno Furlan',
                'contact_info' => 'contato@brunofurlan.com | +55 11 99999-0011 | Instagram: @brunofurlan',
            ],
            [
                'name' => 'Liu',
                'contact_info' => 'booking@liuofficial.com | +55 11 99999-0012 | Instagram: @liu',
            ],
            [
                'name' => 'Gabe',
                'contact_info' => 'contato@gabemusic.com | +55 11 99999-0013 | Instagram: @gabe',
            ],
            [
                'name' => 'Lowderz',
                'contact_info' => 'booking@lowderz.com | +55 11 99999-0014 | Instagram: @lowderz',
            ],
            [
                'name' => 'Rocksted',
                'contact_info' => 'contato@rocksted.com | +55 11 99999-0015 | Instagram: @rocksted',
            ],
            [
                'name' => 'Ratier',
                'contact_info' => 'booking@ratier.com | +55 11 99999-0016 | Instagram: @ratier',
            ],
            [
                'name' => 'Meca',
                'contact_info' => 'contato@mecamusic.com | +55 11 99999-0017 | Instagram: @meca',
            ],
            [
                'name' => 'Antdot',
                'contact_info' => 'booking@antdot.com | +55 11 99999-0018 | Instagram: @antdot',
            ],
            [
                'name' => 'Bhaskar',
                'contact_info' => 'contato@bhaskarmusic.com | +55 11 99999-0019 | Instagram: @bhaskar',
            ],
            [
                'name' => 'Sevenn',
                'contact_info' => 'booking@sevennmusic.com | +55 11 99999-0020 | Instagram: @sevenn',
            ],

            // DJs Techno/Underground
            [
                'name' => 'Victor Ruiz',
                'contact_info' => 'contato@victorruiz.com | +55 11 99999-0021 | Instagram: @victorruiz',
            ],
            [
                'name' => 'Wehbba',
                'contact_info' => 'booking@wehbba.com | +55 11 99999-0022 | Instagram: @wehbba',
            ],
            [
                'name' => 'Fernanda Martins',
                'contact_info' => 'contato@fernandamartins.com | +55 11 99999-0023 | Instagram: @fernandamartins',
            ],
            [
                'name' => 'Eli Iwasa',
                'contact_info' => 'booking@eliiwasa.com | +55 11 99999-0024 | Instagram: @eliiwasa',
            ],
            [
                'name' => 'Renato Cohen',
                'contact_info' => 'contato@renatocohen.com | +55 11 99999-0025 | Instagram: @renatocohen',
            ],

            // DJs House/Deep House
            [
                'name' => 'Maz',
                'contact_info' => 'booking@mazmusic.com | +55 11 99999-0026 | Instagram: @maz',
            ],
            [
                'name' => 'Beowülf',
                'contact_info' => 'contato@beowulf.com | +55 11 99999-0027 | Instagram: @beowulf',
            ],
            [
                'name' => 'Vintage Culture B2B Mau P',
                'contact_info' => 'booking@specialsets.com | +55 11 99999-0028 | Instagram: @vintageculture',
            ],
            [
                'name' => 'Öwnboss',
                'contact_info' => 'contato@ownboss.com | +55 11 99999-0029 | Instagram: @ownboss',
            ],
            [
                'name' => 'Jetlag Music',
                'contact_info' => 'booking@jetlagmusic.com | +55 11 99999-0030 | Instagram: @jetlagmusic',
            ],
        ];

        $created = 0;
        foreach ($artists as $artistData) {
            $model = Artist::updateOrCreate(
                ['name' => $artistData['name']],
                ['contact_info' => $artistData['contact_info']]
            );
            if ($model->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("$created novos artistas de música eletrônica criados.");
    }
}
