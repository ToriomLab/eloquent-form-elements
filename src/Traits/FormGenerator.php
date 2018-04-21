<?php
namespace ToriomLab\EloquentFormElements\Traits;

trait FormGenerator
{
    protected static $initialInput           = 'input';
    protected static $initialInputType       = 'text';
    protected static $initialLabelClasses    = 'control-label col-md-4';
    protected static $initialInputDivClasses = 'col-md-6';
    protected static $initialInputClasses    = 'form-control';

    /**
     * Generate form fields code.
     *
     * @param  array|string $except
     * @param  int|null $id
     * @return string
     */
    public static function generateFormFields(int $id = null, $except = null): string
    {
        // If it's an update form get the current object
        $current = $id != null ? self::find($id) : null;

        // Initial code variable
        $formCode = '';

        // Loop through fields in the model
        foreach (static::$fields as $key => $props) {

            // Except some fields
            if ($except != null) {

                // If the except variable is an array
                if (is_array($except)) {
                    if (in_array($key, $except)) {
                        continue;
                    }
                }

                // If it's a string
                if ($except == $key) {
                    continue;
                }

            }
            

            // Generate the inputs code theirselves
            $inputsCode = static::generateFieldCode($key, $props, $current);

            
            // Set initial values
            // The real variables which will be injected to the generated
            // htnk code will use camelCase and the developer preferences will
            // be in snake_case
            $labelClasses = static::$initialLabelClasses;
            $inputDivClasses = static::$initialInputDivClasses;

            // Extract Props
            extract($props);
            
            // If label classes are provided
            if (isset($props['label_classes'])) {
                // Override the initial one
                $labelClasses = $label_classes;
            }

            if (isset($props['input_div_classes'])) {

                $inputDivClasses = $input_div_classes;
            }

            $inputContainerId = '';
            $inputContainerClasses = '';
            $inputContainerAttributes = '';

            if (isset($props['input_container_id'])) {

                $inputContainerId = $input_container_id;
            }

            if (isset($props['input_container_classes'])) {

                $inputContainerClasses = $input_container_classes;
            }

            if (isset($props['input_container_attributes'])) {

                $inputContainerAttributes = $input_container_attributes;
            }

            // Concat the field code
            $formCode .= "
            <div class='form-group' id='$inputContainerId' class='$inputContainerClasses' $inputContainerAttributes>
                <label for='$key' class='$labelClasses'>$label</label>
                <div class='".$inputDivClasses."'>
                $inputsCode
                </div>
            </div>";

        }

        return $formCode;
    }

    /**
     * Generate an input field code.
     *
     * @param  string $key
     * @param  array  $props
     * @param  object|null $current
     * @return string
     */
    public static function generateFieldCode(string $key, array $props, $current = null): string
    {

        // Initiate code varaible
        $fieldCode = '';

        // Extract props
        extract($props);

        // It there's not input provided so it will be the
        // initial one
        if (!isset($props['input'])) {
            $input = static::$initialInput;
        }

        switch ($input) {

            // In case the input is a normal input tag
            case 'input':
                $fieldCode = static::generateInputCode($key, $props, $current);
                break;
            case 'select':
                $fieldCode = static::generateSelectCode($key, $props, $current);
                break;
            case 'textarea':
                $fieldCode = static::generateTextAreaCode($key, $props, $current);
                break;
        }

        return $fieldCode;
    }

    /**
     * Generate a normal input tag field.
     * @param  string $key
     * @param  array  $props
     * @param  object|null $current
     * @return string
     */
    public static function generateInputCode(string $key, array $props, $current): string
    {
        $inputClasses = static::$initialInputClasses;
        $inputId = $key;

        // Extract props
        extract($props);

        // If there's a custom input tag classes
        if (isset($props['input_classes'])) {

            $inputClasses = $input_classes;
        }

        //  If there's a custom input tag classes
        if (isset($props['input_id'])) {

            $inputId = $input_id;
        }

        // If there's no type provided the input type will be
        // the initial one.
        if (!isset($props['type'])) {
            $type = static::$initialInputType;
        }

        // if there's an inject_attributes
        $injectAttributes = isset($props['inject_attributes']) ? $inject_attributes : '';


        // Basice input code
        $inputCode = "
                <input type='$type' class='$inputClasses' name='$key' id='$inputId' $injectAttributes 
                ";

        // If it's an update input
        if ($current != null) {
            
            $value = isset($valueCallback) ? call_user_func([$current, $valueCallback]) : $current->{$key};
            // Then add the value
            $inputCode .= " value='" . $value . "'";

        }
        $inputCode .= "/>";

        return $inputCode;
    }

    /**
     * Generate a select tag field.
     * @param  string $key
     * @param  array  $props
     * @param  object|null $current
     * @return string
     */
    public static function generateSelectCode(string $key, array $props, $current): string
    {
        $inputClasses = static::$initialInputClasses;
        $inputId = $key;

        // Extract props
        extract($props);

        // If there's a custom input tag classes
        if (isset($props['input_classes'])) {

            $inputClasses = $input_classes;
        }

        //  If there's a custom input tag classes
        if (isset($props['input_id'])) {

            $inputId = $input_id;
        }

        // if there's an inject_attributes
        $injectAttributes = isset($props['inject_attributes']) ? $inject_attributes : '';

        // Basic select code
        $selectCode = "
                <select class='$inputClasses' name='$key' id='$inputId' $injectAttributes>
        ";

        // Initiate options code
        $optionsCode = '';

        if (isset($options)) {

            // If there is static options
            foreach ($options as $value => $label) {
                $selected = '';
                if (isset($current)) {
                    if ($current->{$key} == $value) {
                        $selected .= "selected='selected'";
                    }
                }

                $optionsCode .= "<option value='$value' $selected>$label</option>";
            }

        }

        // It there's a relation key in props
        // we wil lget the select options from this relation.
        if (isset($relation)) {

            // Initiate relation records query
            $allRecordsQuery = call_user_func($relation['model'] .'::latest');

            // If there's a scope
            if (isset($relation['scope'])) {
                $allRecordsQuery = $allRecordsQuery->{$relation['scope']}();
            }

            // Get the records
            $allRecords = $allRecordsQuery->get();

            foreach ($allRecords as $record) {

                // Initiate if selected variable
                $ifSelected = '';

                // If it's an update select field
                if ($current != null) {
                    // BelongsTo Relation 
                    if ($relation['type'] == 'one') {
                        if ($current->{$relation['column']} == $record->id) {
                               $ifSelected = "selected='selected'";
                        }
                    // Has Many or Belongs To Many
                    } elseif ($relation['type'] == 'many') {
                        $relatedIDs = $current->{$relation['name']}()->pluck('id')->toArray();

                        if (in_array($record->id, $relatedIDs)) {
                            $ifSelected = "selected='selected'";
                        }
                    }
                }

                // Concat the option code
                $optionsCode .= "<option value='".$record->{$relation['valueFrom']}."' $ifSelected>"
                                .$record->{$relation['selectFrom']}
                                ."</option>";
            }

        }

        // If there's a valueCallback function
        if (isset($valueCallback) || isset($updateValueFallback) || isset($createValueFallback)) {
            //dd($current->getCategoriesExceptMe());

            // Get All records
            if (isset($updateValueFallback) || isset($createValueFallback)) {
                $function = isset($current) ? call_user_func(array($current, $updateValueFallback)) :
                call_user_func(get_class($current).'::'.$createValueFallback);
            } else {
                $function = is_string($valueFallback) ? call_user_func(get_class($current).'::'.$valueFallback) : $valueFallback;
            }
            $allRecords = $function;

            // All options records loop
            foreach ($allRecords as $record) {

                $ifSelected = '';

                if (isset($current)) {
                
                    // Selected ids
                    // If it's a normal callbacks
                    if (!isset($updateValueFallback)) {
                        $selected_ids = collect(call_user_func([$current, $valueCallback]))->pluck($valueFrom)->all();

                    // If there's createValue and updateValue so it's manual relation
                    } else {
                        $selected_ids = isset($valueCallback) ? collect(call_user_func([$current, $valueCallback]))->all() : (array) $current->{$column};
                    }

                    if (in_array($record->{$valueFrom}, $selected_ids)) {
                        $ifSelected = "selected='selected'";
                    }       
                }
                // Concat the option code
                $optionsCode .= "<option value='".$record->{$valueFrom}."' $ifSelected>".$record->{$selectFrom}."</option>";
            }
        }

        $selectCode .= $optionsCode;

        $selectCode .= "</select>";

        return $selectCode;
    }

    /**
     * Generate a textarea tag field.
     * @param  string $key
     * @param  array  $props
     * @param  object|null $current
     * @return string
     */
    public static function generateTextAreaCode(string $key, array $props, $current): string
    {
        $inputClasses = static::$initialInputClasses;
        $inputId = $key;

        // Extract props
        extract($props);

        if (isset($props['input_classes'])) {

            $inputClasses = $input_classes;
        }

        if (isset($props['input_id'])) {

            $inputId = $input_id;
        }

        $injectAttributes = isset($props['inject_attributes']) ? $inject_attributes : '';

        // Basice input code
        $inputCode = "<textarea class='$inputClasses' name='$key' id='$inputId' $injectAttributes>";

        // If it's an update input
        if ($current != null) {
            // Then add the value
            $inputCode .= $current->{$key};
        }
        $inputCode .= "</textarea>";

        return $inputCode;
    }
}
