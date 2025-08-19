<?php

namespace App\Providers;

use App\Services\LLM\Anthropic;
use App\Services\LLM\Embedder;
use App\Services\LLM\Fireworks;
use App\Services\LLM\LLM;
use App\Services\LLM\LLMFactory;
use App\Services\LLM\OpenAI;
use App\Services\VectorStore\VectorStore;
use App\Services\VectorStore\VectorStoreFactory;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $this->app
            ->when(Anthropic::class)
            ->needs('$apiKey')
            ->give(config('services.anthropic.api_key'));

        $this->app
            ->when(Fireworks::class)
            ->needs('$apiKey')
            ->give(config('services.fireworks.api_key'));

        $this->app
            ->when(OpenAI::class)
            ->needs('$apiKey')
            ->give(config('services.openai.api_key'));

        $this->app
            ->bind(LLM::class, function () {
                return LLMFactory::create();
            });

        $this->app
            ->when(OpenAI::class)
            ->needs('$apiKey')
            ->give(config('services.openai.api_key'));

        $this->app
            ->bind(Embedder::class, function () {
                return LLMFactory::createEmbedder();
            });

        $this->app
            ->bind(VectorStore::class, function () {
                return VectorStoreFactory::create();
            });
    }
}
