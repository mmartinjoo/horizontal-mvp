<?php

namespace App\Providers;

use App\Integrations\Communication\Jira\JiraOAuthService;
use App\Services\GraphDB\GraphDB;
use App\Services\GraphDB\Memgraph;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Anthropic;
use App\Services\LLM\Embedder;
use App\Services\LLM\Fireworks;
use App\Services\LLM\LLM;
use App\Services\LLM\LLMFactory;
use App\Services\LLM\OpenAI;
use App\Services\Search\SearchEngine;
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

        $this->app
            ->when(JiraOAuthService::class)
            ->needs('$clientId')
            ->give(config('services.jira.client_id'));

        $this->app
            ->when(JiraOAuthService::class)
            ->needs('$clientSecret')
            ->give(config('services.jira.client_secret'));

        $this->app
            ->when(JiraOAuthService::class)
            ->needs('$redirectUri')
            ->give(config('services.jira.redirect_uri'));

        $this->app->bind(GraphDB::class, function () {
            return new Memgraph(config('graphdb.connections.memgraph'));
        });

        $this->app
            ->when(SearchEngine::class)
            ->needs('$cosineSimilarityThreshold')
            ->give(config('search_engine.cosine_similarity_threshold'));
    }
}
