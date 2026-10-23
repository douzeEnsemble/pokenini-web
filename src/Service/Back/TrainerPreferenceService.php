<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\HomePage;

class TrainerPreferenceService extends AbstractBackService
{
    public function getHomePage(): HomePage
    {
        $json = $this->requestContent('GET', '/trainer/preference');

        /** @var array{home_page?: mixed} $content */
        $content = json_decode($json, true) ?? [];

        $homePage = $content['home_page'] ?? '';

        return new HomePage(\is_string($homePage) ? $homePage : '');
    }

    public function modifyHomePage(HomePage $homePage): void
    {
        $this->request(
            'PUT',
            '/trainer/preference',
            [
                'body' => json_encode(['home_page' => $homePage->value], JSON_THROW_ON_ERROR),
            ]
        );
    }
}
