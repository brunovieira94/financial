<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\Types\Resource_;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //JsonResource::withoutWrapping();

        Schema::defaultStringLength(191);
        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $relationName = explode('.', $attribute);
                            $columnSearch = $relationName[count($relationName)-1];
                            unset($relationName[count($relationName)-1]);
                            $relationName = implode('.', $relationName);
                            $query->orWhereHas($relationName, function (Builder $query) use ($searchTerm, $columnSearch) {
                                $query->where($columnSearch, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });
            return $this;
        });
    }
}
