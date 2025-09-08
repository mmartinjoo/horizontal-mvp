<?php

namespace App\Jobs;

use App\Services\GraphDB\GraphDB;
use App\Services\LLM\Embedder;
use App\Services\LLM\LLM;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexGraphCommunity implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $communityID,
        private string $communityLevel,
        // The text information from the nodes inside a community
        private string $nodeNames,
        // The full text from the original file chunk
        private string $chunkText,
    ) {
    }

    public function handle(
        LLM $llm,
        GraphDB $graphDB,
        Embedder $embedder,
    ) {
        $result = $llm->completion("
            ## Task: Generate Community Summary for Knowledge Graph Cluster

            You are analyzing a community (cluster) of related concepts from a knowledge graph. This community was identified through graph-based community detection, meaning these concepts are densely interconnected and likely share common themes or contexts.

            ### Input Data:

            **Community level:**
            {$this->communityLevel}

            **Extracted Concepts from Nodes/children of this community:**
            {$this->nodeNames}

            **Source Context from the file chunk that the nodes are mentioned in:**
            {$this->chunkText}

            ### Your Task:

            Analyze the provided concepts and their source contexts to generate a concise, meaningful summary that captures:

            1. **Core Theme**: What is the primary subject or domain this community represents?
            2. **Key Relationships**: What are the main connections or relationships between these concepts?
            3. **Contextual Purpose**: Why are these concepts grouped together? What business/technical/conceptual purpose do they serve?
            4. **Distinctive Characteristics**: What makes this community distinct from others in the knowledge graph?

            ### Guidelines for a Good Summary:

            - **Be Specific**: Avoid generic descriptions. Instead of \"business concepts,\" write \"customer acquisition strategy for B2B SaaS targeting technical decision-makers\"
            - **Identify Patterns**: Look for recurring themes, entities, or relationships across the concepts
            - **Preserve Hierarchy**: If this is a higher-level community (level > 0), focus on broader themes; for level 0 communities, be more specific
            - **Use Domain Language**: Maintain the vocabulary and terminology present in the source material
            - **Be Actionable**: The summary should help someone quickly understand what knowledge this community contains

            ### Output Format:
            {
                \"title\": \"A descriptive 3-7 word title for this community\",
                \"summary\": \"A 1-2 sentence description that captures the essence of this community. Be specific about the domain, key entities, and relationships.\"
            }

            ### Example Output:
            {
                \"title\": \"B2B SaaS Customer Acquisition Strategy\",
                \"summary\": \"Focuses on targeting technical decision-makers (CTOs, VPs of Engineering) at small to medium-sized companies for B2B SaaS products. It encompasses go-to-market strategies, ideal customer profiles, and specific outreach tactics.\"
            }

            ---

            Now, generate the summary for the community based on the provided data.
        ");

        $data = json_decode($result, true);
        $embedding = $embedder->createEmbedding($data['title'] . ' ' . $data['summary']);
        $embeddingStr = json_encode($embedding);
        $graphDB->run("
            match (c:Community { id: {$this->communityID}, level: {$this->communityLevel} })
            set
                c.name = \"{$data['title']}\",
                c.summary = \"{$data['summary']}\",
                c.embedding = {$embeddingStr};
        ");
    }
}
