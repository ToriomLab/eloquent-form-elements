<?php
namespace ToriomLab\EloquentFormElements\Traits;
use Session;

trait FormGenerator
{
    protected static $initialInput           = 'input';
    protected static $initialInputType       = 'text';
    protected static $initialLabelClasses    = 'control-label col-md-2';
    protected static $initialInputDivClasses = 'col-md-5';
    protected static $initialInputClasses    = 'form-control';
    public static $model;

    protected static function bootFormGenerator()
    {
        static::$model = new self();
    }

    /**
     * Generate form fields code.
     *
     * @param  array|string $except
     * @param  int|null $id
     * @return string
     */
    public static function generateFormFields(int $id = null, $except = null): string
    {

        try{
            if($id != null){
                $current = $id != null ? self::dontRemember()->find($id) : null;
            }else{
                $current = null;
            }
        }catch (\Exception $e){
            $current = $id != null ? self::find($id) : null;
        }

        if(!empty(Session::get('_old_input'))){
            if($current==null){
                $current = (object) Session::get('_old_input');
            }else{
                $old = (object) Session::get('_old_input');
                foreach ($current->attributes as $key => $value){
                    if(isset($old->{$key})){
                        $current->{$key} = $old->{$key};
                    }
                }
            }
        }

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
            $inputSubLable = '';

            if (isset($props['input_container_id'])) {

                $inputContainerId = $input_container_id;
            }

            if (isset($props['input_container_classes'])) {

                $inputContainerClasses = $input_container_classes;
            }

            if (isset($props['input_container_attributes'])) {

                $inputContainerAttributes = $input_container_attributes;
            }

            if(isset($props['sub_title'])) {
                $inputSubLable = $sub_title;
            }
            if(@$props['element'] != 'hr') {
                // Is img element
                if(@$props['element'] == 'img')
                    $imageId = str_replace('_preview', '', $key);
                // Is img element empty
                if(@$props['element'] == 'img' && ($current == null || empty($current->{$imageId}))){
                    $formCode .= '';
                }
                else
                    // Concat the field code
                    $formCode .= "
                <div class='form-group $inputContainerClasses' id='$inputContainerId' $inputContainerAttributes>
                    <label for='$key' class='$labelClasses'>$label</label>
                    <div class='".$inputDivClasses."'>
                        ". $inputsCode ."
                        ". $inputSubLable ."
                    </div>
                </div>";

            } else {
                $formCode .= $inputsCode;
            }

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
        if (!isset($props['input']) && !isset($props['element'])) {
            $input = static::$initialInput;
        }

        if(isset($props['input'])) {
            switch ($input) {

                // In case the input is a normal input tag
                case 'input':
                    // translatable
                    if (isset(static::$model->translatable) && in_array($key, static::$model->translatable)) {
                        $fieldCode = '';
                        $injectAttributes = isset($props['inject_attributes']) ? $inject_attributes : '';
                        foreach (config('multilang.locales') as $lang_code => $lang_text) {
                            $fieldCode .= static::generateInputCode($key, $props, $current, $lang_code, $lang_text) . '<br>';
                        }
                    } else
                        $fieldCode = static::generateInputCode($key, $props, $current);
                    break;
                case 'select':
                    $fieldCode = static::generateSelectCode($key, $props, $current);
                    break;
                case 'textarea':
                    // translatable
                    if (isset(static::$model->translatable) && in_array($key, static::$model->translatable)) {
                        $fieldCode = '';
                        foreach (config('multilang.locales') as $lang_code => $lang_text) {
                            if(isset($props['input_classes']) && $props['input_classes'] == 'editor')
                                $fieldCode .= '<strong>'. $lang_text .'</strong>';
                            $fieldCode .= static::generateTextAreaCode($key, $props, $current, $lang_code, $lang_text) . '<br>';
                        }
                    } else
                        $fieldCode = static::generateTextAreaCode($key, $props, $current);
                    break;
            }
        }

        if(isset($props['element'])) {
            switch ($element) {
                case 'hr':
                    $fieldCode = static::generateLineCode($props);
                    break;
                case 'img':
                    $fieldCode = static::generateImageCode($key, $props, $current);
                    break;
            }
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
    public static function generateInputCode(string $key, array $props, $current, $lang = null, $lang_text = null): string
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


        $input_name = $key;
        $placeholder = '';
        // translatable
        if (isset(static::$model->translatable) && in_array($key, static::$model->translatable)) {
            try{
                if(!empty($current) && isset($current->id)){
                    $current = $current->in($lang,false);
                }else{
                    $current = null;
                }
            } catch (\Exception $e) {
                $current = null;
            }
            $input_name = $lang . '['. $key .']';
            $placeholder = $lang_text;
        }


        // Basice input code
        $inputCode = "
                <input type='$type' class='$inputClasses' name='$input_name' id='$inputId' $injectAttributes  placeholder='$placeholder'
                ";

        // If it's an update input
        if ($current != null) {
            $value = isset($valueCallback) ? call_user_func([$current, $valueCallback]) : @$current->$key;
            // Then add the value
            $inputCode .= " value='" . $value . "'";

        } else if(@$current == null && !empty($valueCallback)) {
            $value = isset($valueCallback) ? call_user_func(get_called_class().'::'.$valueCallback) : '';
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
        if ((isset($valueCallback) || isset($updateValueFallback) || isset($createValueFallback))) {

            // Get All records
            if (isset($updateValueFallback) || isset($createValueFallback)) {
                $function = isset($current) ? call_user_func(array($current, $updateValueFallback)) :
                    call_user_func(get_called_class().'::'.$createValueFallback);
            } else {
                try{
                    $function = is_string($valueFallback) ? call_user_func(get_class($current).'::'.$valueFallback) : $valueFallback;
                } catch (\Exception $e) {
                    $function = call_user_func(get_called_class().'::'.$valueFallback);
                }
            }
            $allRecords = $function;

            // All options records loop
            if(!is_string($allRecords)){
                foreach ($allRecords as $record) {

                    $ifSelected = '';

                    if (isset($current)) {
                        // Selected ids
                        // If it's a normal callbacks
                        if (!isset($updateValueFallback)) {
                            try{
                                $selected_ids = collect(call_user_func([$current, $valueCallback]))->pluck($valueFrom)->all();
                            }catch(\Exception $e) {
                                $selected_ids  = [];
                            }
                            // If there's createValue and updateValue so it's manual relation
                        } else {
                            // $selected_ids = isset($valueCallback) ? collect(call_user_func([$current, $valueCallback]))->all() : (array) $current->{$column};
                            $selected_ids = (array) $current->{$key};
                        }

                        if (in_array($record->{$valueFrom}, $selected_ids)) {
                            $ifSelected = "selected='selected'";
                        }
                    }
                    // Concat the option code
                    $optionsCode .= "<option value='".$record->{$valueFrom}."' $ifSelected>".$record->{$selectFrom}."</option>";
                }
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
    public static function generateTextAreaCode(string $key, array $props, $current, $lang = null, $lang_text = null): string
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

        $input_name = $key;
        $placeholder = '';
        // translatable
        if (isset(static::$model->translatable) && in_array($key, static::$model->translatable)) {
            try{
                if(!empty($current) && isset($current->id)){
                    $current = $current->in($lang,false);
                }else{
                    $current = null;
                }
            } catch (\Exception $e) {
                $current = null;
            }
            $input_name = $lang . '['. $key .']';
            $placeholder = $lang_text;
        }

        // Basice input code
        $inputCode = "<textarea class='$inputClasses' name='$input_name' id='$inputId' $injectAttributes placeholder='$placeholder'>";

        // If it's an update input
        if ($current != null) {
            // Then add the value
            $inputCode .= $current->$key;
        }
        $inputCode .= "</textarea>";

        return $inputCode;
    }

    /**
     * Generate a html line between fields.
     * @param  array  $props
     * @return string
     */
    public static function generateLineCode(array $props): string
    {
        extract($props);
        $elementClasses  = '';
        if (isset($props['input_classes'])) {

            $elementClasses = $input_classes;
        }

        $lineCode = "<hr class='$elementClasses'/>";

        return $lineCode;
    }

    /**
     * Generate a html line between fields.
     * @param  array  $props
     * @return string
     */
    public static function generateImageCode(string $key, array $props, $current): string
    {
        $imageClasses   = '';
        $imageId        = str_replace('_preview', '', $key);
        $imageCode      = '';
        if($current != null)
        {
            $src = isset($valueCallback) ? call_user_func([@$current, $valueCallback]) : @$current->{$imageId};
            if(!empty($src))
            {
                // Extract props
                extract($props);

                // If there's a custom image tag classes
                if (isset($props['image_classes'])) {

                    $imageClasses = $image_classes;
                }

                // if there's an inject_attributes
                $injectAttributes = isset($props['inject_attributes']) ? $inject_attributes : '';

                // Basice image code
                $imageCode = "<img class='$imageClasses' $injectAttributes ";

                // Then add the value
                $imageCode .= " src='" . asset($props['storage_folder'] . $src) . "'";

                $imageCode .= "/>";
            }
        }
        return $imageCode;
    }
}
