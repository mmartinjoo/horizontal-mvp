import os
import asyncio
# from openai import OpenAI
from llama_index.core import Document, KnowledgeGraphIndex
from llama_index.core.graph_stores import SimpleGraphStore
# from llama_index.core.extractors import SimpleLLMPathExtractor
from llama_index.core.indices.property_graph import SimpleLLMPathExtractor, SchemaLLMPathExtractor, DynamicLLMPathExtractor
from llama_index.core import Settings, VectorStoreIndex, PropertyGraphIndex, KnowledgeGraphIndex
from llama_index.core.node_parser import SentenceSplitter
from llama_index.llms.openai import OpenAI
from llama_index.graph_stores.memgraph import MemgraphPropertyGraphStore
from typing import Literal, List, Tuple

os.environ["OPENAI_API_KEY"] = "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"
llm = OpenAI(
    api_key=os.environ["OPENAI_API_KEY"],
    temperature=0,
    model="gpt-4o"
)
Settings.llm = llm

text = """
Customer profile
With Horizontal, we want to target smaller companies and early-stage startups. Mainly for two reasons:
They don’t like overcomplicated enterprise software
I have access to some of them
Horizontal will be a cool, startup-friendly (developer-friendly) tool.
"""

documents = [Document(text=text)]

def simple():
    index = VectorStoreIndex.from_documents(documents)
    query_engine = index.as_query_engine()
    response = query_engine.query("Who is Steve Jobs?")
    print(response)
    
def schema_based():
    entities = Literal["PERSON", "ORGANIZATION", "LOCATION", "PRODUCT", "EVENT"]
    relations = Literal["WORKS_AT", "LOCATED_IN", "PART_OF", "DEVELOPED", "COLLABORATED_WITH"]

    # Create validation schema for consistent extraction
    schema = {
        "PERSON": ["WORKS_AT", "COLLABORATED_WITH"],
        "ORGANIZATION": ["LOCATED_IN", "DEVELOPED", "PART_OF"],
        "LOCATION": ["PART_OF"],
        "PRODUCT": ["DEVELOPED"],
        "EVENT": ["LOCATED_IN", "PART_OF"]
    }

    # Configure extractor with schema validation
    kg_extractor = SchemaLLMPathExtractor(
        llm=llm,
        possible_entities=entities,
        possible_relations=relations,
        kg_validation_schema=schema,
        strict=True,  # Enforce schema compliance
        max_triplets_per_chunk=15,
        num_workers=4
    )
    
    index = PropertyGraphIndex.from_documents(
        documents,
        max_triplets_per_chunk=5,
        llm=llm,
        include_embeddings=True,
        kg_extractors=[kg_extractor],
    )
    
    retriever = index.as_retriever(
        include_text=True,
    )
    nodes = retriever.retrieve("Who is Steve Jobs?")
    print(nodes)
    
def simple_based():
    graph_store = MemgraphPropertyGraphStore(
        password="",
        username="",
        url="bolt://127.0.0.1:7687"
    )
    kg_extractor = SimpleLLMPathExtractor(
        llm=llm, 
        max_paths_per_chunk=20, 
        num_workers=4,
    )
    simple_index = PropertyGraphIndex.from_documents(
        documents,
        llm=llm,
        embed_kg_nodes=True,
        kg_extractors=[kg_extractor],
        show_progress=True,
        property_graph_store=graph_store,
    )
    xs = simple_index.property_graph_store.get_triplets(
        entity_names=["Horizontal"]
    )
    print(xs)
    
def dynamic_based():
    kg_extractor = DynamicLLMPathExtractor(
        llm=llm,
        max_triplets_per_chunk=20,
        num_workers=4,
        # Let the LLM infer entities and their labels (types) on the fly
        allowed_entity_types=None,
        # Let the LLM infer relationships on the fly
        allowed_relation_types=None,
        # LLM will generate any entity properties, set `None` to skip property generation (will be faster without)
        allowed_relation_props=[],
        # LLM will generate any relation properties, set `None` to skip property generation (will be faster without)
        allowed_entity_props=[],
    )

    dynamic_index = PropertyGraphIndex.from_documents(
        documents,
        llm=llm,
        embed_kg_nodes=False,
        kg_extractors=[kg_extractor],
        show_progress=True,
    )

    xs = dynamic_index.property_graph_store.get_triplets(
        entity_names=["Apple"]
    )
    print(xs)
    
simple_based()