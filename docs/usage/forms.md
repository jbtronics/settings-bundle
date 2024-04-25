---
title: Forms
layout: default
parent: Usage
nav_order: 5
---

# Forms

The settings-bundle comes with an integration with symfony/forms, which allows you to easily create forms for your settings classes. The fields of the form, their form type and options are automatically derived from the settings metadata like the property types and attributes. This way allows you to easily create forms for your settings classes without having to manually create the form fields and use a declarative approach.

## Retrieving form builders

To get a form builder containing form fields for the parameters of a settings class, you can use the `SettingsFormFactoryInterface`. There is the `createSettingsFormBuilder()` method to create a form builder for a single settings class, where each form field is a direct child of the form builder. Use the `createMultiSettingsFormBuilder()` method, if you wanna create a form for multiple settings classes at once. In the case of the multi-settings form, the root form builder contains a subform for each settings class.

The data of the form builder is already assigned to the passed instance of the settings class, so the form fields are already filled with the current values of the settings class and changes to the form fields are automatically reflected in the settings instance.

The form builder can be used as normal, and you can add additional (non-mapped) forms fields, etc. to it. To build a simple settings form, you have to just add a submit button to the form builder, check for form submission and save the settings.

Especially if you want to modify a settings object which is used to modify the behavior of critical parts of your application, it is recommended to pass a temporary copy of the settings object to the form builder. The way how symfony forms work, the form builder will modify the passed object directly and even invalid data will be written to the object if the form is submitted. This can lead to unexpected behavior or exceptions, if the settings object is used in other parts of the application and these parts see invalid data.

Therefore you should create a temporary copy of the settings object, pass it to the form and merge the data back to the original object, if the form is valid.

```php
class SettingsFormController {

    public function __construct(
        private SettingsManagerInterface $settingsManager,
        private SettingsFormFactoryInterface $settingsFormFactory,
        ) {}

    #[Route('/settings', name: 'settings')]
    public function settingsForm(Request $request): Response
    {
        //Create a temporary copy of the settings object
        $clone = $this->settingsManager->createTemporaryCopy(TestSettings::class);

        //Create a builder for the settings form
        $builder = $this->settingsFormFactory->createSettingsFormBuilder($clone);

        //Add a submit button, so we can save the form
        $builder->add('submit', SubmitType::class);

        //Create the form
        $form = $builder->getForm();

        //Handle the form submission
        $form->handleRequest($request);

        //If the form was submitted and the data is valid, then it
        if ($form->isSubmitted() && $form->isValid()) {
            //Merge the clone containing the modified data back into the managed instance
            $this->settingsManager->mergeTemporaryCopy($clone);

            //Save the settings
            $this->settingsManager->save();
        }

        //Render the form
        return $this->render('settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

## Form types and options

In many cases, the form types and required options are automatically derived from the parameter types of the settings parameters. For example, if you have a string property in your settings class and mark it as a settings parameter, normally the `StringType` parameter type will be used, who uses the `TextType` form field by default.

You can customize the used form type and the options used to render a certain parameter via the `formType` and `formOptions` options of the `#[SettingsParameter]` attribute. These options override the default form type and options given by the bundle and the parameter types:

```php
#[SettingsParameter(formType: EmailType::class, formOptions: ['trim' => false])]
private string $myString;
```

This is also required if the parameter type dont define any default form options.

By default the form field label is derived from the label of the parameter if set, otherwise the property name will be used. The description option of the parameter attribute will be shown as help text below the form field. By using the `formOptions` options, you can customize this further:

```php
#[SettingsParameter(label: 'Field Label', description: 'This will be shown as help text')]
private string $myString;

#[SettingsParameter(label: 'Label2', description: 'This will be shown as help text', formOptions: ['label' => '<b>HTML</b> label', label_html => true])]
private string $value2;
```

## Rendering only particular parameters

If you want to render only a subset of the parameters (or embedded settings) of a settings class, you can pass an array of groups to the `groups` parameter of the `createSettingsFormBuilder()` or `createMultiSettingsFormBuilder()` methods. Only parameters which are in one of the given groups will be rendered. If no groups are given, all parameters will be rendered.

The groups are defined at the `#[SettingsParameter]` attribute. If no group is given, the parameter is in the default group. You can also use the `groups` option of the `#[Settings]` attribute to define a default group for all parameters of the settings class, where it is not explicitly defined.

```php

//All parameters without a group are in the default group
#[Settings(groups: ['defaultGroup'])]
class GroupedSettings
{

    #[SettingsParameter(groups: ['group1'])]
    private string $myString;

    #[SettingsParameter(groups: ['group1', 'group2'])]
    private string $myString2

    #[SettingsParameter(groups: ['group2', 'group3'])]
    private string $myString3;
}

```

```php

//This will only render the parameters in the group1 group (myString and myString2)
$builder = $this->settingsFormFactory->createSettingsFormBuilder($settings, groups: ['group1']);
```

## Embedded settings

Embedded settings in a settings class are recursively rendered as subforms.
This means you can define complex settings forms with nested subforms, by using the root settings (or any other node) of your settings hierarchy.

If you have a non-tree structure (with circular references), the form builder will throw an exception, as it is not possible to render a circular form. In those cases, you will need to restrict the rendered form fields using the `groups` option.