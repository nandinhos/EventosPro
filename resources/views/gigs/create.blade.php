<x-app-layout>
     
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <x-gigs.form
                        :action="route('gigs.store')"
                        submit-label="Criar Gig"
                        :artists="$artists"
                        :bookers="$bookers"
                        :costCenters="$costCenters"
                        :tags="$tags"
                    />
                </div>
            </div>
        </div>
    
</x-app-layout>