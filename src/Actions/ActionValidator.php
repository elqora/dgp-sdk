<?php

namespace Elqora\Dgp\Actions;

final class ActionValidator
{
    /**
     * @param list<ActionButton> $buttons
     * @return array<string, string>
     */
    public static function validateButtons(array $buttons): array
    {
        $errors = [];

        foreach ($buttons as $index => $button) {
            if (!$button instanceof ActionButton) {
                $errors["buttons.{$index}"] = 'Button must be an ActionButton instance.';
                continue;
            }

            foreach (self::validateButton($button) as $path => $error) {
                $errors["buttons.{$index}.{$path}"] = $error;
            }
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    public static function validateButton(ActionButton $button): array
    {
        $errors = [];

        if (trim($button->value) === '') {
            $errors['value'] = 'Button value is required.';
        }

        if (($button->kind === ActionButtonKind::TEXT || $button->kind === ActionButtonKind::TEXT_ICON)
            && ($button->label === null || trim($button->label) === '')
        ) {
            $errors['label'] = 'Text buttons require a label.';
        }

        if (($button->kind === ActionButtonKind::ICON || $button->kind === ActionButtonKind::TEXT_ICON)
            && ($button->icon === null || trim($button->icon) === '')
        ) {
            $errors['icon'] = 'Icon buttons require an icon.';
        }

        if ($button->kind === ActionButtonKind::ICON
            && ($button->tooltip === null || trim($button->tooltip) === '')
        ) {
            $errors['tooltip'] = 'Icon-only buttons should include a tooltip.';
        }

        if ($button->disabledReason !== null
            && trim($button->disabledReason) !== ''
            && $button->disabled !== true
        ) {
            $errors['disabled_reason'] = 'Disabled reason requires disabled=true.';
        }

        if ($button->nextAction !== null && $button->value !== 'action') {
            $errors['next_action'] = 'Buttons with a next action must use value "action".';
        }

        if ($button->value === 'action' && $button->nextAction === null) {
            $errors['next_action'] = 'Button value "action" requires a next action.';
        }

        return $errors;
    }
}
