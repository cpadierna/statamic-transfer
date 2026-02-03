<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Statamic Component Transfer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div id="app" class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-2 text-white">Statamic Component Transfer GUI</h1>
        <p class="text-gray-400 mb-8 max-w-3xl">
            Quickly transfer components between Statamic projects. This tool scans for Antlers templates (.antlers.html), Vue components (.vue), JavaScript files (.js), and YAML configs (.yaml/.yml) in your source project. Select files to add them to the transfer queue, then execute to copy them to your destination project. <span class="text-yellow-400">Currently only supports Statamic-to-Statamic transfers.</span>
        </p>

        <!-- Project Paths Section -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <!-- Source Project -->
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-1">Original Project</h2>
                <p class="text-gray-400 text-sm mb-4">The project you want to grab components/files from</p>
                <div class="mb-4">
                    <select
                        v-model="sourcePath"
                        @change="browseSource"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm"
                    >
                        <option value="">Select a project...</option>
                        <option v-for="p in herdProjects" :key="p.path" :value="p.path">
                            @{{ p.name }} <span v-if="p.isStatamic">(Statamic)</span>
                        </option>
                    </select>
                </div>
                <div v-if="sourceFiles.length" class="mb-3">
                    <input
                        v-model="fileFilter"
                        type="text"
                        placeholder="Filter files by name..."
                        class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm"
                    >
                </div>
                <div v-if="filteredSourceFiles.length" class="max-h-64 overflow-y-auto">
                    <div
                        v-for="file in filteredSourceFiles"
                        :key="file.path"
                        @click="addToQueue(file)"
                        class="flex items-center gap-2 p-2 hover:bg-gray-700 rounded cursor-pointer text-sm"
                    >
                        <span class="text-xs px-2 py-1 rounded" :class="fileTypeClass(file.type)">
                            @{{ file.type }}
                        </span>
                        <span class="truncate">@{{ file.path }}</span>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-sm">Enter project path and click Load</p>
            </div>

            <!-- Destination Project -->
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-1">New Project</h2>
                <p class="text-gray-400 text-sm mb-4">The project you want to import/add files to</p>
                <div class="mb-4">
                    <select
                        v-model="destPath"
                        @change="browseDest"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm"
                    >
                        <option value="">Select a project...</option>
                        <option v-for="p in herdProjects" :key="p.path" :value="p.path">
                            @{{ p.name }} <span v-if="p.isStatamic">(Statamic)</span>
                        </option>
                    </select>
                </div>
                <div v-if="destIsStatamic" class="text-green-400 text-sm">
                    ✓ Valid Statamic project
                </div>
            </div>
        </div>

        <!-- Transfer Queue Table -->
        <div class="bg-gray-800 rounded-lg p-4 mb-8">
            <h2 class="text-lg font-semibold mb-2">Transfer Queue</h2>
            <p class="text-gray-400 text-sm mb-4">
                Use the <strong class="text-gray-300">Label</strong> column to organize and name components as you work.
                <strong class="text-gray-300">Dest Path</strong> defaults to the same relative path in your new project—edit it to change where the file goes.
                <strong class="text-gray-300">New Name</strong> defaults to the original filename—edit it to rename the file.
            </p>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left py-2 px-2 w-32">Label</th>
                        <th class="text-left py-2 px-2">From</th>
                        <th class="text-left py-2 px-2">Source Path</th>
                        <th class="text-left py-2 px-2">Goes</th>
                        <th class="text-left py-2 px-2">Dest Path</th>
                        <th class="text-left py-2 px-2">New Name</th>
                        <th class="text-left py-2 px-2 w-20"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in transferQueue" :key="index" class="border-b border-gray-700">
                        <td class="py-2 px-2">
                            <input
                                v-model="item.label"
                                type="text"
                                :placeholder="'File' + (index + 1)"
                                class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm"
                            >
                        </td>
                        <td class="py-2 px-2 text-gray-400">From</td>
                        <td class="py-2 px-2 text-blue-400 text-sm">
                            @{{ item.relativePath }}
                        </td>
                        <td class="py-2 px-2 text-gray-400">Goes</td>
                        <td class="py-2 px-2 relative group">
                            <input
                                v-model="item.destDir"
                                type="text"
                                :placeholder="suggestDestPath(item)"
                                class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm"
                            >
                            <div v-if="item.destDir || suggestDestPath(item)" class="absolute left-0 bottom-full mb-1 hidden group-hover:block bg-gray-900 border border-gray-600 rounded px-2 py-1 text-xs text-white whitespace-nowrap z-10 max-w-lg break-all">
                                @{{ item.destDir || suggestDestPath(item) }}
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <input
                                v-model="item.newName"
                                type="text"
                                :placeholder="item.originalName"
                                class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm"
                            >
                        </td>
                        <td class="py-2 px-2">
                            <button
                                @click="removeFromQueue(index)"
                                class="text-red-400 hover:text-red-300"
                            >
                                ✕
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!transferQueue.length">
                        <td colspan="7" class="py-4 text-center text-gray-500">
                            Click files from the source project to add them to the queue
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <button
                @click="executeTransfer"
                :disabled="!transferQueue.length || !destPath"
                class="bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed px-6 py-2 rounded font-semibold"
            >
                Execute Transfer
            </button>
            <button
                @click="clearQueue"
                class="bg-gray-600 hover:bg-gray-700 px-6 py-2 rounded"
            >
                Clear Queue
            </button>
        </div>

        <!-- Results -->
        <div v-if="results.length" class="mt-8 bg-gray-800 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4">Transfer Results</h2>
            <div v-for="result in results" :key="result.source" class="py-2 border-b border-gray-700 last:border-0">
                <span v-if="result.success" class="text-green-400">✓</span>
                <span v-else class="text-red-400">✕</span>
                <span class="ml-2">@{{ result.source }} → @{{ result.destination }}</span>
                <span v-if="result.error" class="text-red-400 text-sm block ml-6">@{{ result.error }}</span>
            </div>
        </div>
    </div>

    <script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                sourcePath: '',
                destPath: '',
                sourceFiles: [],
                destIsStatamic: false,
                transferQueue: [],
                results: [],
                herdProjects: [],
                fileFilter: ''
            }
        },
        async mounted() {
            await this.loadHerdProjects();
        },
        computed: {
            filteredSourceFiles() {
                if (!this.fileFilter.trim()) {
                    return this.sourceFiles;
                }
                const filter = this.fileFilter.toLowerCase();
                return this.sourceFiles.filter(file =>
                    file.name.toLowerCase().includes(filter) ||
                    file.path.toLowerCase().includes(filter)
                );
            }
        },
        methods: {
            async loadHerdProjects() {
                try {
                    const res = await fetch('/herd-projects');
                    const data = await res.json();
                    this.herdProjects = data.projects;
                } catch (e) {
                    console.error('Failed to load Herd projects', e);
                }
            },
            async browseSource() {
                if (!this.sourcePath) {
                    this.sourceFiles = [];
                    return;
                }
                try {
                    const res = await fetch('/browse', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ path: this.sourcePath })
                    });
                    const data = await res.json();
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.sourceFiles = data.files;
                } catch (e) {
                    alert('Failed to load project');
                }
            },
            async browseDest() {
                if (!this.destPath) {
                    this.destIsStatamic = false;
                    return;
                }
                try {
                    const res = await fetch('/browse', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ path: this.destPath })
                    });
                    const data = await res.json();
                    this.destIsStatamic = data.isStatamic;
                } catch (e) {
                    alert('Failed to load project');
                }
            },
            addToQueue(file) {
                this.transferQueue.push({
                    label: '',
                    sourcePath: file.fullPath,
                    originalName: file.name,
                    relativePath: file.path,
                    destDir: '',
                    newName: ''
                });
            },
            removeFromQueue(index) {
                this.transferQueue.splice(index, 1);
            },
            suggestDestPath(item) {
                if (!this.destPath) return '';
                // Suggest same relative path in destination
                const dir = item.relativePath.substring(0, item.relativePath.lastIndexOf('/'));
                return this.destPath + '/' + dir;
            },
            fileTypeClass(type) {
                const classes = {
                    antlers: 'bg-purple-600',
                    vue: 'bg-green-600',
                    yaml: 'bg-yellow-600',
                    js: 'bg-blue-600',
                    other: 'bg-gray-600'
                };
                return classes[type] || classes.other;
            },
            clearQueue() {
                this.transferQueue = [];
                this.results = [];
            },
            async executeTransfer() {
                const transfers = this.transferQueue.map(item => ({
                    sourcePath: item.sourcePath,
                    destPath: item.destDir || this.suggestDestPath(item),
                    newName: item.newName || item.originalName
                }));

                try {
                    const res = await fetch('/execute', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ transfers })
                    });
                    const data = await res.json();
                    this.results = data.results;
                } catch (e) {
                    alert('Transfer failed');
                }
            }
        }
    }).mount('#app');
    </script>
</body>
</html>
