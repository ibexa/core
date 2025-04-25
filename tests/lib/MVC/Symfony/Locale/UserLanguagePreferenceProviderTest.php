<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Locale;

use Ibexa\Contracts\Core\Repository\UserPreferenceService;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreference;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Locale\UserLanguagePreferenceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

class UserLanguagePreferenceProviderTest extends TestCase
{
    private const string LOCALE_FALLBACK = 'en';
    private const string LANGUAGE_PREFERENCE_NAME = 'language';
    private const string LANGUAGE_PREFERENCE_VALUE = 'no';

    private UserLanguagePreferenceProvider $userLanguagePreferenceProvider;

    private RequestStack & MockObject $requestStackMock;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);

        $userLanguagePreference = new UserPreference([
            'name' => self::LANGUAGE_PREFERENCE_NAME,
            'value' => self::LANGUAGE_PREFERENCE_VALUE,
        ]);

        $userPreferenceServiceMock = $this->createMock(UserPreferenceService::class);
        $userPreferenceServiceMock
            ->method('getUserPreference')
            ->with(self::LANGUAGE_PREFERENCE_NAME)
            ->willReturn($userLanguagePreference);

        $this->userLanguagePreferenceProvider = new UserLanguagePreferenceProvider(
            $this->requestStackMock,
            $userPreferenceServiceMock,
            $this->getLanguageCodesMap(),
            self::LOCALE_FALLBACK
        );
    }

    /**
     * @dataProvider providerForTestGetPreferredLanguages
     *
     * @param array<string> $userLanguages
     * @param array<string> $expectedLanguageCodes
     */
    public function testGetPreferredLanguagesWithoutUserLanguage(array $userLanguages, array $expectedLanguageCodes): void
    {
        $request = new Request();
        $request->headers = new HeaderBag(
            [
                'Accept-Language' => implode(', ', $userLanguages),
            ]
        );
        $this
            ->requestStackMock
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $userPreferenceServiceMock = $this->createMock(UserPreferenceService::class);
        $userPreferenceServiceMock
            ->method('getUserPreference')
            ->with(self::LANGUAGE_PREFERENCE_NAME)
            ->willThrowException(new NotFoundException('User Preference', self::LANGUAGE_PREFERENCE_NAME));

        $userLanguagePreferenceProvider = new UserLanguagePreferenceProvider(
            $this->requestStackMock,
            $userPreferenceServiceMock,
            $this->getLanguageCodesMap(),
            self::LOCALE_FALLBACK
        );

        self::assertEquals(
            $expectedLanguageCodes,
            $userLanguagePreferenceProvider->getPreferredLanguages()
        );
    }

    /**
     * @dataProvider providerForTestGetPreferredLanguagesWithUserPreferredLanguage
     *
     * @param array $userLanguages
     * @param array $expectedEzLanguageCodes
     */
    public function testGetPreferredLanguagesWithUserPreferredLanguage(array $userLanguages, array $expectedEzLanguageCodes): void
    {
        $request = new Request();
        $request->headers = new HeaderBag(
            [
                'Accept-Language' => implode(', ', $userLanguages),
            ]
        );
        $this
            ->requestStackMock
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals(
            $expectedEzLanguageCodes,
            $this->userLanguagePreferenceProvider->getPreferredLanguages()
        );
    }

    /**
     * @see testGetPreferredLanguages
     *
     * @return array
     */
    public function providerForTestGetPreferredLanguages(): array
    {
        return [
            [[], ['eng-GB', 'eng-US']],
            [['pl'], ['pol-PL']],
            [['fr'], ['fre-FR']],
            [['en'], ['eng-GB', 'eng-US']],
            [['en_us'], ['eng-US']],
        ];
    }

    /**
     * @see testGetPreferredLanguages
     *
     * @return array
     */
    public function providerForTestGetPreferredLanguagesWithUserPreferredLanguage(): array
    {
        return [
            [[], ['nor-NO', 'eng-GB', 'eng-US']],
            [['pl'], ['nor-NO', 'pol-PL']],
            [['fr'], ['nor-NO', 'fre-FR']],
            [['en'], ['nor-NO', 'eng-GB', 'eng-US']],
            [['en_us'], ['nor-NO', 'eng-US']],
        ];
    }

    private function getLanguageCodesMap(): array
    {
        $config = Yaml::parseFile(
            realpath(dirname(__DIR__, 5) . '/src/bundle/Core/Resources/config/locale.yml')
        );

        return $config['parameters']['ibexa.locale.browser_map'];
    }
}
