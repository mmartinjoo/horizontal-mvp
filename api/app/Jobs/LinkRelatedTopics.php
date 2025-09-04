<?php

namespace App\Jobs;

use App\Models\Topic;
use App\Services\GraphDB\GraphDB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class LinkRelatedTopics implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function handle(GraphDB $graphDB)
    {
        $topics = Topic::all();
        foreach ($topics as $topic) {
            $embeddingStr = '[' . implode(',', $topic->embedding) . ']';

            // <=> returns cosine distance
            // (1 - cosine_distance) returns cosine similarity
            $relatedTopics = Topic::query()
                ->selectRaw('*, 1 - (embedding <=> ?) as score', [$embeddingStr])
                ->whereRaw('embedding IS NOT NULL')
                ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
                ->orderByDesc('score')
                ->limit(10)
                ->get();

            foreach ($relatedTopics as $relatedTopic) {
                if ($topic->id === $relatedTopic->id) {
                    continue;
                }
                $exists = DB::table('topic_relations')
                    ->where('topic_id', $topic->id)
                    ->where('related_topic_id', $relatedTopic->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $exists = DB::table('topic_relations')
                    ->where('related_topic_id', $topic->id)
                    ->where('topic_id', $relatedTopic->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                logger('Linking topic ' . $topic->name . ' to ' . $relatedTopic->name . ' with similarity ' . $relatedTopic->score);

                DB::table('topic_relations')
                    ->insert([
                        'topic_id' => $topic->id,
                        'related_topic_id' => $relatedTopic->id,
                        'similarity' => $relatedTopic->score,
                    ]);
                $graphDB->addRelation(
                    fromNodeLabel: 'Topic',
                    fromNodeID: $topic->id,
                    relation: 'RELATED_TO',
                    toNodeLabel: 'Topic',
                    toNodeID: $relatedTopic->id,
                    relationAttributes: [
                        'similarity' => $relatedTopic->score,
                    ],
                );
            }
        }
    }
}
