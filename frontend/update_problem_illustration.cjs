const fs = require('fs');

// Read the current App.vue file
let appVue = fs.readFileSync('src/App.vue', 'utf8');

// Find the empty problem illustration section
const oldSection = `      <!-- Problem Illustration -->
      <div class="max-w-5xl mx-auto">
        <div class="bg-gray-50 rounded-3xl p-12 border border-gray-100">
          
        </div>
      </div>`;

// Replace with the new image section
const newSection = `      <!-- Problem Illustration -->
      <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-3xl p-8 shadow-lg border border-gray-100">
          <img 
            src="/images/lots_of_shitty_apps_in_one_place.png" 
            alt="Too many scattered apps and tools" 
            class="w-full h-auto rounded-2xl"
          />
        </div>
      </div>`;

// Replace the section
appVue = appVue.replace(oldSection, newSection);

// Write the updated file
fs.writeFileSync('src/App.vue', appVue);
console.log('Problem illustration updated with custom image!');