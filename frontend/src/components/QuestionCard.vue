<template>
  <div class="question-card bg-white rounded-3xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer"
       :class="{ 'expanded': isExpanded }"
       @click="toggleExpanded">

    <!-- Card Header -->
    <div class="flex items-start space-x-4 mb-4">
      <!-- Icon -->
      <div class="flex-shrink-0">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
             :class="iconBgClass">
          <component :is="iconComponent" class="w-6 h-6" :class="iconColorClass" />
        </div>
      </div>

      <!-- Question -->
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900 leading-relaxed">
          {{ question }}
        </h3>
      </div>
    </div>

    <!-- Quick Answer -->
    <div class="mb-4">
      <div class="flex items-start space-x-2">
        <p class="text-sm font-medium">
          {{ quickAnswer }}
        </p>
      </div>
    </div>

    <!-- Expandable Sources -->
    <div class="sources-container" :class="{ 'expanded': isExpanded }">
      <div v-if="isExpanded" class="sources-list pt-4 border-t border-gray-100">
        <div class="mb-4">
          <span class="text-sm font-medium text-gray-700">📍 Sources found across your tools:</span>
        </div>

        <div class="space-y-3">
          <div
            v-for="(source, index) in sources"
            :key="index"
            class="source-item flex items-start space-x-4 p-4 bg-gray-50 rounded-xl border border-gray-100 hover:bg-gray-100 transition-colors"
            :style="{ animationDelay: `${index * 100}ms` }"
          >
            <!-- Source Type Indicator -->
            <div class="flex-shrink-0">
              <div class="w-3 h-3 rounded-full" :class="getSourceColorClass(source.type)"></div>
            </div>

            <!-- Source Content -->
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-2">
                <span class="text-sm font-medium text-gray-800">
                  {{ getSourceIcon(source.type) }} {{ source.title }}
                </span>
                <button class="text-blue-400 text-sm hover:text-blue-600 transition-colors">
                  {{ getSourceActionText(source.type) }}
                </button>
              </div>
              <p class="text-sm text-gray-600">{{ source.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- View Context Button -->
    <div class="mt-6">
      <div class="flex items-center justify-center">
        <button class="flex items-center space-x-2 text-sm text-purple-600 hover:text-purple-700 font-medium transition-colors">
          <span>{{ isExpanded ? 'Hide Context' : 'View Context' }}</span>
          <svg
            class="w-4 h-4 transition-transform duration-300"
            :class="{ 'rotate-180': isExpanded }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  question: String,
  quickAnswer: String,
  sources: Array,
  iconType: String
})

const isExpanded = ref(false)

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

const iconComponent = computed(() => {
  const icons = {
    issue: 'IssueIcon',
    tasks: 'TasksIcon',
    scope: 'ScopeIcon'
  }
  return icons[props.iconType] || 'IssueIcon'
})

const iconBgClass = computed(() => {
  const classes = {
    issue: 'bg-red-50',
    tasks: 'bg-orange-50',
    scope: 'bg-blue-50'
  }
  return classes[props.iconType] || 'bg-red-50'
})

const iconColorClass = computed(() => {
  const classes = {
    issue: 'text-red-500',
    tasks: 'text-orange-500',
    scope: 'text-blue-500'
  }
  return classes[props.iconType] || 'text-red-500'
})

const getSourceColorClass = (type) => {
  const classes = {
    slack: 'bg-orange-500',
    linear: 'bg-purple-500',
    github: 'bg-gray-700',
    notion: 'bg-blue-500',
    analytics: 'bg-green-500'
  }
  return classes[type] || 'bg-gray-500'
}

const getSourceIcon = (type) => {
  const icons = {
    slack: '💬',
    linear: '🐛',
    github: '💻',
    notion: '📝',
    analytics: '📊'
  }
  return icons[type] || '📄'
}

const getSourceActionText = (type) => {
  return 'Open';
}
</script>

<script>
// Icon components
const IssueIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 24 24">
      <ellipse cx="12" cy="13" rx="6" ry="7" fill="currentColor" opacity="0.9"/>
      <circle cx="12" cy="7" r="3" fill="currentColor"/>
      <circle cx="10.5" cy="6.5" r="0.8" fill="white"/>
      <circle cx="13.5" cy="6.5" r="0.8" fill="white"/>
      <circle cx="10.5" cy="6.5" r="0.4" fill="black"/>
      <circle cx="13.5" cy="6.5" r="0.4" fill="black"/>
      <path d="M9 4.5 L8 2.5 M15 4.5 L16 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <circle cx="8" cy="2.5" r="0.8" fill="currentColor"/>
      <circle cx="16" cy="2.5" r="0.8" fill="currentColor"/>
      <path d="M6 10 L3 9 M6 13 L3 13 M6 16 L3 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M18 10 L21 9 M18 13 L21 13 M18 16 L21 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <circle cx="10" cy="12" r="0.8" fill="white" opacity="0.7"/>
      <circle cx="14" cy="14" r="0.6" fill="white" opacity="0.7"/>
      <circle cx="12" cy="16" r="0.5" fill="white" opacity="0.7"/>
    </svg>
  `
}

const TasksIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
    </svg>
  `
}

const ScopeIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
  `
}

export default {
  components: {
    IssueIcon,
    TasksIcon,
    ScopeIcon
  }
}
</script>

<style scoped>
.question-card {
  max-height: 300px;
  overflow: hidden;
  transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.question-card.expanded {
  max-height: 800px;
}

.sources-container {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sources-container.expanded {
  max-height: 600px;
}

.sources-list {
  opacity: 0;
  transform: translateY(-10px);
  transition: opacity 0.2s ease 0.1s, transform 0.2s ease 0.1s;
}

.question-card.expanded .sources-list {
  opacity: 1;
  transform: translateY(0);
}

.source-item {
  opacity: 0;
  transform: translateY(-5px);
  animation: slideInSource 0.3s ease forwards;
}

@keyframes slideInSource {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
