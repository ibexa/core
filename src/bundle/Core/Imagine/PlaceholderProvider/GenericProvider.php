<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\PlaceholderProvider;

use Ibexa\Bundle\Core\Imagine\PlaceholderProvider;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Imagine\Image;
use Imagine\Image\AbstractFont;
use Imagine\Image\ImagineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenericProvider implements PlaceholderProvider
{
    private ImagineInterface $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    public function getPlaceholder(ImageValue $value, array $options = []): string
    {
        $options = $this->resolveOptions($options);

        $palette = new Image\Palette\RGB();
        $background = $palette->color($options['background']);
        $foreground = $palette->color($options['foreground']);
        $secondary = $palette->color($options['secondary']);

        $size = new Image\Box($value->width ?? 0, $value->height ?? 0);
        $font = $this->imagine->font($options['fontpath'], $options['fontsize'], $foreground);
        if (!$font instanceof AbstractFont) {
            throw new \LogicException("Font {$options['fontpath']} is not an instance of AbstractFont");
        }

        $text = $this->getPlaceholderText($options['text'], $value);

        $center = new Image\Point\Center($size);
        $textbox = $font->box($text);
        $textpos = new Image\Point(
            max($center->getX() - ($textbox->getWidth() / 2), 0),
            max($center->getY() - ($textbox->getHeight() / 2), 0)
        );

        $image = $this->imagine->create($size, $background);
        $image->draw()->line(
            new Image\Point(0, 0),
            new Image\Point($value->width ?? 0, $value->height ?? 0),
            $secondary
        );

        $image->draw()->line(
            new Image\Point($value->width ?? 0, 0),
            new Image\Point(0, $value->height ?? 0),
            $secondary
        );

        $image->draw()->text($text, $font, $textpos, 0, $value->width);

        $path = $this->getTemporaryPath();
        $image->save($path, [
            'format' => pathinfo($value->id, PATHINFO_EXTENSION),
        ]);

        return $path;
    }

    private function getPlaceholderText(string $pattern, ImageValue $value): string
    {
        return strtr($pattern, [
            '%width%' => $value->width,
            '%height%' => $value->height,
            '%id%' => $value->id,
        ]);
    }

    private function getTemporaryPath(): string
    {
        return stream_get_meta_data(tmpfile())['uri'];
    }

    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'background' => '#EEEEEE',
            'foreground' => '#000000',
            'secondary' => '#CCCCCC',
            'fontsize' => 20,
            'text' => "IMAGE PLACEHOLDER %width%x%height%\n(%id%)",
        ]);
        $resolver->setRequired('fontpath');

        return $resolver->resolve($options);
    }
}
