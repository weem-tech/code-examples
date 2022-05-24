<?php

namespace App\Transformers;

use App\Models\Action;
use App\Models\SubscriptionPlan;
use Fifth\Generator\Common\Transformer;
use Illuminate\Database\Eloquent\Model;

class ActionTransformer extends Transformer
{
    public function forSubscriptionPlanTransform(Action $action, SubscriptionPlan $subscriptionPlan): array
    {
        return array_merge($this->simpleTransform($action), [
            'is_active' => $this->isActiveForPlan($subscriptionPlan, $action),
        ]);
    }
    /**
     * @OA\Schema(
     *   schema="ActionSimple",
     *   type="object",
     *   @OA\Property(property="id",  type="integer"),
     *   @OA\Property(property="name",  type="string"),
     * )
     * @param Model $model
     * @return array
     */
    public function simpleTransform(Model $model): array
    {
        return [
            'id'    => $model->id,
            'name'  => $model->name,
        ];
    }

    private function isActiveForPlan(SubscriptionPlan $subscriptionPlan, Action $action): bool
    {
        return $subscriptionPlan->actions->where('id', $action->id)->isNotEmpty();
    }
}
