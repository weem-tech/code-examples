<?php

namespace App\Http\Validators;


class BaseValidator
{
    public function existsOnModel($attribute, $value, $parameters, $validator): bool
    {
        if (!$parameters[0]) return true;

        return $this->existsOnAnyTable($this->getModelClassByName($parameters[0]), $attribute, $value, $parameters, $validator);
    }

    public function notExistsOnModel($attribute, $value, $parameters, $validator): bool
    {
        if (!$parameters[0]) return true;

        return !$this->existsOnAnyTable($this->getModelClassByName($parameters[0]), $attribute, $value, $parameters, $validator);
    }

    public function existsOnPivot($attribute, $value, $parameters, $validator): bool
    {
        return $this->existsOnAnyTable($this->getPivotClassByName($parameters[0]), $attribute, $value, $parameters, $validator);
    }

    private function existsOnAnyTable($model, $attribute, $value, $parameters, $validator): bool
    {
        $column = $parameters[1] ?? $attribute;

        $expected = (is_array($value)) ? count($value) : 1;

        $query = $model::whereIn($column, (array) $value);

        $this->applyScope($model, $query, $parameters[2] ?? null, $parameters[3] ?? null, $parameters[4] ?? null);

        return $query->whereIn($column, (array) $value)->count() >= $expected;
    }

    private function applyScope($model, &$query, $scopeName, $modelId, $scopeModel)
    {
        if(!$scopeName){
            return;
        }
        if (method_exists($model, 'scope' . ucfirst($scopeName))) {
            $query->{$scopeName}($scopeModel, $modelId);
        } else {
            throw new \Exception("scope not found for model $model");
        }
    }

    private function getModelClassByName(string $modelName): string
    {
        // if model name was given without namespace, then we will guess it supposing that it in App\Models directory
        if (!preg_match('~\\\\~', $modelName)) {
            return modelClass($modelName);
        }

        return $modelName;
    }

    private function getPivotClassByName(string $pivotName): string
    {
        if (!preg_match('~\\\\~', $pivotName)) {
            return pivotClass($pivotName);
        }

        return $pivotName;
    }
}
