<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Bundle\ThemeBundle\Templating\Locator;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\ThemeBundle\Context\ThemeContextInterface;
use Sylius\Bundle\ThemeBundle\Locator\ResourceNotFoundException;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Sylius\Bundle\ThemeBundle\Templating\Locator\TemplateFileLocator;
use Sylius\Bundle\ThemeBundle\Templating\Locator\TemplateLocatorInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * @mixin TemplateFileLocator
 *
 * @author Kamil Kokot <kamil.kokot@lakion.com>
 */
class TemplateFileLocatorSpec extends ObjectBehavior
{
    function let(
        FileLocatorInterface $decoratedFileLocator,
        ThemeContextInterface $themeContext,
        TemplateLocatorInterface $templateLocator
    ) {
        $this->beConstructedWith($decoratedFileLocator, $themeContext, $templateLocator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Bundle\ThemeBundle\Templating\Locator\TemplateFileLocator');
    }

    function it_implements_file_locator_interface()
    {
        $this->shouldImplement(FileLocatorInterface::class);
    }

    function it_throws_an_exception_if_located_thing_is_not_an_instance_of_template_reference_interface()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->during('locate', ['not an instance']);
    }

    function it_returns_first_possible_theme_resource(
        ThemeContextInterface $themeContext,
        TemplateLocatorInterface $templateLocator,
        TemplateReferenceInterface $template,
        ThemeInterface $firstTheme,
        ThemeInterface $secondTheme
    ) {
        $themeContext->getThemeHierarchy()->willReturn([$firstTheme, $secondTheme]);

        $templateLocator->locateTemplate($template, $firstTheme)->willThrow(ResourceNotFoundException::class);
        $templateLocator->locateTemplate($template, $secondTheme)->willReturn('/second/theme/template/path');
        
        $this->locate($template)->shouldReturn('/second/theme/template/path');
    }

    function it_falls_back_to_decorated_template_locator_if_themed_tempaltes_can_not_be_found(
        FileLocatorInterface $decoratedFileLocator,
        ThemeContextInterface $themeContext,
        TemplateLocatorInterface $templateLocator,
        TemplateReferenceInterface $template,
        ThemeInterface $theme
    ) {
        $themeContext->getThemeHierarchy()->willReturn([$theme]);

        $templateLocator->locateTemplate($template, $theme)->willThrow(ResourceNotFoundException::class);

        $decoratedFileLocator->locate($template, Argument::cetera())->willReturn('/app/template/path');

        $this->locate($template)->shouldReturn('/app/template/path');
    }

    function it_falls_back_to_decorated_template_locator_if_there_are_no_themes_active(
        FileLocatorInterface $decoratedFileLocator,
        ThemeContextInterface $themeContext,
        TemplateReferenceInterface $template
    ) {
        $themeContext->getThemeHierarchy()->willReturn([]);

        $decoratedFileLocator->locate($template, Argument::cetera())->willReturn('/app/template/path');

        $this->locate($template)->shouldReturn('/app/template/path');
    }
}
