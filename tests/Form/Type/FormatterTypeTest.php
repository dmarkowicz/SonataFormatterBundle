<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\FormatterBundle\Tests\Form\Type;

use FOS\CKEditorBundle\Model\ConfigManagerInterface;
use FOS\CKEditorBundle\Model\PluginManagerInterface;
use FOS\CKEditorBundle\Model\TemplateManagerInterface;
use FOS\CKEditorBundle\Model\ToolbarManagerInterface;
use PHPUnit\Framework\TestCase;
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Formatter\Pool;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class FormatterTypeTest extends TestCase
{
    public function testBuildFormOneChoice()
    {
        $pool = $this->createMock(Pool::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $type = new FormatterType($pool, $translator, $configManager);

        $choiceFormBuilder = $this->createMock(FormBuilderInterface::class);
        $choiceFormBuilder->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->will($this->returnValue(['foo' => 'bar']));

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects($this->exactly(3))->method('add');
        $formBuilder->expects($this->once())->method('get')->will($this->returnValue($choiceFormBuilder));
        $formBuilder->expects($this->once())->method('remove');

        $options = [
            'format_field' => 'format',
            'source_field' => 'source',
            'format_field_options' => [
                'property_path' => '',
            ],
            'source_field_options' => [
                'property_path' => '',
            ],
            'listener' => false,
        ];

        $type->buildForm($formBuilder, $options);
    }

    public function testBuildFormSeveralChoices()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $type = new FormatterType($pool, $translator, $configManager);

        $choiceFormBuilder = $this->createMock(FormBuilderInterface::class);
        $choiceFormBuilder->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->will($this->returnValue(['foo' => 'bar', 'foo2' => 'bar2']));

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects($this->exactly(2))->method('add');
        $formBuilder->expects($this->once())->method('get')->will($this->returnValue($choiceFormBuilder));

        $options = [
            'format_field' => 'format',
            'source_field' => 'source',
            'format_field_options' => [
                'property_path' => '',
            ],
            'source_field_options' => [
                'property_path' => '',
            ],
            'listener' => false,
        ];

        $type->buildForm($formBuilder, $options);
    }

    public function testBuildFormWithCustomFormatter()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $formatters = ['text' => 'Text', 'html' => 'HTML', 'markdown' => 'Markdown'];

        $selectedFormat = 'html';

        $pool->method('getFormatters')->will($this->returnValue($formatters));
        $type = new FormatterType($pool, $translator, $configManager);

        $choiceFormBuilder = $this->createMock(FormBuilderInterface::class);
        $choiceFormBuilder->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->will($this->returnValue($formatters));

        $options = [
            'format_field' => 'SomeFormatField',
            'source_field' => 'SomeSourceField',
            'format_field_options' => [
                'property_path' => '',
                'data' => $selectedFormat,
                'choices' => $formatters,
            ],
            'source_field_options' => [
                'property_path' => '',
            ],
            'listener' => false,
        ];

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects($this->at(0))->method('add')->with('SomeFormatField', ChoiceType::class, [
            'property_path' => 'SomeFormatField',
            'data' => $selectedFormat,
            'choices' => $formatters,
        ]);
        $formBuilder->expects($this->at(1))->method('get')->will($this->returnValue($choiceFormBuilder));
        $formBuilder->expects($this->at(2))->method('add')->with('SomeSourceField', TextareaType::class, [
            'property_path' => 'SomeSourceField',
        ]);

        $type->buildForm($formBuilder, $options);
    }

    public function testBuildFormWithDefaultFormatter()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $configManager = $this->createMock('FOS\CKEditorBundle\Model\ConfigManagerInterface');

        $formatters = ['text' => 'Text', 'html' => 'HTML', 'markdown' => 'Markdown'];
        $defaultFormatter = 'text';

        $pool->method('getFormatters')->will($this->returnValue($formatters));
        $pool->method('getDefaultFormatter')->will($this->returnValue('text'));
        $type = new FormatterType($pool, $translator, $configManager);

        $choiceFormBuilder = $this->createMock(FormBuilderInterface::class);
        $choiceFormBuilder->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->will($this->returnValue($formatters));

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects($this->at(0))->method('add')->with('SomeFormatField', ChoiceType::class, [
            'property_path' => 'SomeFormatField',
            'data' => $defaultFormatter,
            'choices' => $formatters,
        ]);
        $formBuilder->expects($this->at(1))->method('get')->will($this->returnValue($choiceFormBuilder));
        $formBuilder->expects($this->at(2))->method('add')->with('SomeSourceField', TextareaType::class, [
            'property_path' => 'SomeSourceField',
        ]);

        $options = [
            'format_field' => 'SomeFormatField',
            'source_field' => 'SomeSourceField',
            'format_field_options' => [
                'property_path' => '',
                'choices' => $formatters,
            ],
            'source_field_options' => [
                'property_path' => '',
            ],
            'listener' => false,
        ];

        $type->buildForm($formBuilder, $options);
    }

    public function testBuildFormWithDefaultFormatterAndPluginManager()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);
        $pluginManager = $this->createMock(PluginManagerInterface::class);

        $formatters = ['text' => 'Text', 'html' => 'HTML', 'markdown' => 'Markdown'];
        $defaultFormatter = 'text';

        $pool->method('getFormatters')->will($this->returnValue($formatters));
        $pool->method('getDefaultFormatter')->will($this->returnValue('text'));
        $type = new FormatterType($pool, $translator, $configManager, $pluginManager);

        $choiceFormBuilder = $this->createMock(FormBuilderInterface::class);
        $choiceFormBuilder->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->will($this->returnValue($formatters));

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects($this->at(0))->method('add')->with('SomeFormatField', ChoiceType::class, [
            'property_path' => 'SomeFormatField',
            'data' => $defaultFormatter,
            'choices' => $formatters,
        ]);
        $formBuilder->expects($this->at(1))->method('get')->will($this->returnValue($choiceFormBuilder));
        $formBuilder->expects($this->at(2))->method('add')->with('SomeSourceField', TextareaType::class, [
            'property_path' => 'SomeSourceField',
        ]);

        $options = [
            'format_field' => 'SomeFormatField',
            'source_field' => 'SomeSourceField',
            'format_field_options' => [
                'property_path' => '',
                'choices' => $formatters,
            ],
            'source_field_options' => [
                'property_path' => '',
            ],
            'listener' => false,
        ];

        $type->buildForm($formBuilder, $options);
    }

    public function testBuildViewWithDefaultConfig()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $defaultConfig = 'default';
        $defaultConfigValues = ['toolbar' => ['Button1']];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));

        $type = new FormatterType($pool, $translator, $configManager);

        /** @var \Symfony\Component\Form\FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => [],
        ]);

        $this->assertSame($view->vars['ckeditor_configuration'], $defaultConfigValues);
    }

    public function testBuildViewWithoutDefaultConfig()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $defaultConfig = 'default';
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(false));

        $type = new FormatterType($pool, $translator, $configManager);

        $ckEditorToolBarIcons = ['Icon 1'];

        /** @var \Symfony\Component\Form\FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => $ckEditorToolBarIcons,
        ]);

        $ckeditorConfiguration = ['toolbar' => $ckEditorToolBarIcons];
        $this->assertSame($view->vars['ckeditor_configuration'], $ckeditorConfiguration);
    }

    public function testBuildViewWithDefaultConfigAndWithToolbarIcons()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $defaultConfig = 'default';
        $defaultConfigValues = ['toolbar' => ['Button 1']];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));

        $type = new FormatterType($pool, $translator, $configManager);

        $ckEditorToolBarIcons = ['Icon 1'];

        /** @var \Symfony\Component\Form\FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => $ckEditorToolBarIcons,
        ]);

        $this->assertSame($view->vars['ckeditor_configuration'], ['toolbar' => $defaultConfigValues['toolbar']]);
    }

    public function testBuildViewWithFormatter()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);

        $type = new FormatterType($pool, $translator, $configManager);

        $ckEditorToolBarIcons = ['Icon 1'];

        $formatters = [];
        $formatters['text'] = 'Text';
        $formatters['html'] = 'HTML';

        $format = 'html';

        /** @var \Symfony\Component\Form\FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => [
                'choices' => $formatters,
                'data' => $format,
            ],
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => $ckEditorToolBarIcons,
        ]);

        $this->assertSame($view->vars['format_field_options']['data'], $format);
    }

    public function testBuildViewWithDefaultConfigAndPluginManager()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);
        $pluginManager = $this->createMock(PluginManagerInterface::class);

        $defaultConfig = 'default';
        $defaultConfigValues = ['toolbar' => ['Button1']];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));

        $type = new FormatterType($pool, $translator, $configManager, $pluginManager);

        /** @var FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => [],
        ]);

        $this->assertSame($view->vars['ckeditor_configuration'], $defaultConfigValues);
    }

    public function testBuildViewWithDefaultConfigAndToolbarManager()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);
        $toolbarManager = $this->createMock(ToolbarManagerInterface::class);

        $defaultConfig = 'default';
        $toolbar_config = 'custom_toolbar';
        $defaultConfigValues = ['toolbar' => $toolbar_config];
        $custom_toolbar = ['Button 1'];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));
        $toolbarManager->expects($this->once())
            ->method('resolveToolbar')
            ->with($toolbar_config)
            ->will($this->returnValue($custom_toolbar));

        $type = new FormatterType($pool, $translator, $configManager, null, null, $toolbarManager);

        /** @var FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => [],
        ]);

        $this->assertSame($view->vars['ckeditor_configuration'], ['toolbar' => $custom_toolbar]);
    }

    public function testBuildViewWithDefaultConfigAndPluginManagerAndTemplateManager()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $configManager = $this->createMock(ConfigManagerInterface::class);
        $pluginManager = $this->createMock(PluginManagerInterface::class);
        $templateManager = $this->createMock(TemplateManagerInterface::class);

        $defaultConfig = 'default';
        $defaultConfigValues = ['toolbar' => ['Button1']];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));

        $type = new FormatterType($pool, $translator, $configManager, $pluginManager, $templateManager);

        /** @var FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => [],
        ]);

        $this->assertSame($view->vars['ckeditor_configuration'], $defaultConfigValues);
    }

    public function testBuildViewWithDefaultConfigAndPluginManagerAndTemplateManagerAndWithTemplates()
    {
        $pool = $this->createMock(Pool::class);
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $configManager = $this->createMock('FOS\CKEditorBundle\Model\ConfigManagerInterface');
        $pluginManager = $this->createMock('FOS\CKEditorBundle\Model\PluginManagerInterface');
        $templateManager = $this->createMock('FOS\CKEditorBundle\Model\TemplateManagerInterface');

        $defaultConfig = 'default';
        $defaultConfigValues = ['toolbar' => ['Button1']];
        $configManager->expects($this->once())->method('getDefaultConfig')->will($this->returnValue($defaultConfig));
        $configManager->expects($this->once())
            ->method('hasConfig')
            ->with($defaultConfig)
            ->will($this->returnValue(true));
        $configManager->expects($this->once())
            ->method('getConfig')
            ->with($defaultConfig)
            ->will($this->returnValue($defaultConfigValues));

        $templates = [
            'imagesPath' => '/bundles/mybundle/templates/images',
            'templates' => [
                [
                    'title' => 'My Template',
                    'image' => 'images.jpg',
                    'description' => 'My awesome template',
                    'html' => '<p>Crazy template :)</p>',
                ],
            ],
        ];

        $templateManager->expects($this->once())->method('hasTemplates')->will($this->returnValue(true));
        $templateManager->expects($this->once())->method('getTemplates')->will($this->returnValue($templates));

        $type = new FormatterType($pool, $translator, $configManager, $pluginManager, $templateManager);

        /** @var FormView $view */
        $view = $this->createMock(FormView::class);
        $view->vars['id'] = 'SomeId';
        $view->vars['name'] = 'SomeName';
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'source_field' => 'SomeField',
            'format_field' => 'SomeFormat',
            'format_field_options' => 'SomeOptions',
            'ckeditor_context' => null,
            'ckeditor_image_format' => null,
            'ckeditor_basepath' => '',
            'ckeditor_plugins' => [],
            'ckeditor_templates' => [],
            'ckeditor_toolbar_icons' => [],
        ]);

        $this->assertSame($view->vars['ckeditor_templates'], $templates);
    }

    public function testOptions()
    {
        $formatters = ['text' => 'Text', 'html' => 'HTML', 'markdown' => 'Markdown'];

        $pool = $this->getMockBuilder('Sonata\FormatterBundle\Formatter\Pool')->disableOriginalConstructor()->getMock();
        $pool->method('getFormatters')->will($this->returnValue($formatters));

        $translator = $this->createMock(TranslatorInterface::class);

        $configManager = $this->createMock(ConfigManagerInterface::class);

        $optionsResolver = new OptionsResolver();

        $type = new FormatterType($pool, $translator, $configManager);
        $type->configureOptions($optionsResolver);

        $options = $optionsResolver->resolve();

        $expectedOptions = [
            'choices' => [
                'text' => 'text',
                'html' => 'html',
                'markdown' => 'markdown',
            ],
            'choice_translation_domain' => 'SonataFormatterBundle',
        ];

        // choices_as_values options is not needed in SF 3.0+
        if (method_exists(FormTypeInterface::class, 'setDefaultOptions')) {
            $expectedOptions['choices_as_values'] = true;
        }

        $this->assertEquals($expectedOptions, $options['format_field_options']);
    }
}
