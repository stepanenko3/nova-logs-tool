<template>
    <div>
        <Heading class="mb-3"> {{ __('Logs') }}</Heading>

        <div
            v-if="files.length > 0"
            class="grid gap-6 md:grid-cols-12 mb-4"
        >
            <div class="relative h-9 md:col-span-4">
                <Icon
                    type="search"
                    width="20"
                    class="absolute ml-2 text-gray-400"
                    :style="{ top: '4px' }"
                />

                <RoundInput
                    class="appearance-none g-white dark:bg-gray-800 shadow rounded-full h-8 w-full dark:focus:bg-gray-800"
                    :placeholder="__('Search')"
                    spellcheck="false"
                    :aria-label="__('Search')" type="search"
                    @input="search = $event.target.value"
                    @keydown.stop="performSearch"
                />
            </div>

            <div class="md:col-span-4 hidden md:flex"></div>

            <div
                v-if="files.length"
                class="md:col-span-4 md:justify-end flex items-center"
                :class="{ disabled: loading }"
            >
                <SelectControl
                    :options="files"
                    v-model:selected="file"
                    @input="file = $event.target.value"
                    @change="changeFile" />

                <div
                    class="md:ml-2 inline-flex items-center shadow rounded-lg bg-white dark:bg-gray-800 px-2 h-8"
                >
                    <ToolbarButton
                        @click.prevent="getLogs"
                        type="refresh"
                        v-tooltip="__('Refresh')"
                    />

                    <ToolbarButton
                        @click.prevent="playing = !playing"
                        :class="{
                            'text-green-500': playing,
                            'text-gray-500': !playing,
                        }"
                        type="clock"
                        class="w-8 h-8"
                        v-tooltip="playing ? __('Stop polling') : __('Start polling')"
                    />

                    <ToolbarButton
                        v-if="permissions.canDownload"
                        @click.prevent="download"
                        type="download"
                        v-tooltip="__('Download')"
                    />

                    <ToolbarButton
                        v-if="permissions.canDelete"
                        @click.prevent="openDeleteModal"
                        type="trash"
                        v-tooltip="__('Delete')"
                    />
                </div>
            </div>
        </div>

        <div
            class="relative"
            :class="{ 'overflow-hidden': loading }"
        >
            <div
                v-if="loading"
                class="flex items-center justify-center z-50 p-6"
                style="min-height: 150px"
            >
                <Loader class="text-60" />
            </div>
            <template v-else>
                <Card>
                    <div class="overflow-y-hidden overflow-x-auto relative">
                        <table v-if="logs.data.length > 0" class="w-full table-default sticky-last-column">
                            <thead>
                                <tr>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Level') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Created at') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Message') }}
                                    </th>
                                    <th
                                        class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                    </th>
                                </tr>
                            </thead>

                            <tbody v-for="(log, index) in logs.data">
                                <tr class="hover:bg-blue-lightest">
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        <span class="whitespace-no-wrap flex items-center">
                                            <Icon
                                                :type="icons[log.level || 'error']"
                                                view-box="0 0 24 24"
                                                width="24"
                                                height="24"
                                                :style="{ color: colors[log.level || 'error'] }"
                                            />
                                            <span class="ml-2">
                                                {{ log.level }}
                                            </span>
                                        </span>
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        {{ log.date }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        {{ log.text }}
                                    </td>

                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 text-right">
                                        <span @click="viewLog(log)">
                                            <Icon
                                                type="eye"
                                                view-box="0 0 24 24"
                                                width="24"
                                                height="24"
                                            />
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="!logs.data.length"
                        class="flex flex-col justify-center items-center px-6 py-8"
                    >
                        <Icon
                            type="search"
                            class="mb-3 text-gray-300 dark:text-gray-500"
                            width="50"
                            height="50"
                        />

                        <h3 class="text-base font-normal mt-3">
                            {{ __('No Logs.') }}
                        </h3>
                    </div>

                    <div class="bg-20 rounded-b">
                        <nav v-if="logs.data.length > 0" class="flex justify-between">
                            <!-- Previous Link -->
                            <button
                                :disabled="!hasPreviousPages"
                                class="btn btn-link py-3 px-4"
                                :class="{
                                    'text-primary dim': hasPreviousPages,
                                    'text-80 opacity-50': !hasPreviousPages
                                }"
                                rel="prev"
                                @click.prevent="selectPreviousPage"
                                dusk="previous"
                            >
                                {{ __('Previous') }}
                            </button>

                            <button class="btn btn-link py-3 px-4 text-50 dim">
                                {{ logs.current_page }} / {{ Math.ceil(logs.total / logs.per_page) }}
                            </button>

                            <!-- Next Link -->
                            <button
                                :disabled="!hasMorePages"
                                class="btn btn-link py-3 px-4"
                                :class="{
                                    'text-primary dim': hasMorePages,
                                    'text-80 opacity-50': !hasMorePages
                                }"
                                rel="next"
                                @click.prevent="selectNextPage()"
                                dusk="next"
                            >
                                {{ __('Next') }}
                            </button>
                        </nav>
                    </div>
                </Card>
            </template>
        </div>

        <Modal
            :show="showLog"
            tabindex="-1"
            data-testid="show-log"
            role="dialog"
            maxWidth="screen-md"
        >
            <div class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="p-6">
                    <Heading level="2" class="whitespace-no-wrap flex items-center justify-center">
                        <Icon
                            :type="icons[showLog.level || 'error']"
                            view-box="0 0 24 24"
                            width="32"
                            height="32"
                            :style="{ color: colors[showLog.level || 'error'] }"
                        />

                        <span class="ml-2">
                            {{ showLog.level.toUpperCase() }}
                        </span>
                    </Heading>

                    <div class="mt-3 mb-4 text-center">
                        {{ showLog.date }}
                    </div>

                    <pre
                        ref="outputCodeMessage"
                        v-if="showLog.text"
                        v-text="'[message]\n' + showLog.text"
                        class="mb-4 block p-4 rounded-lg bg-gray-100 dark:bg-gray-900 w-full text-left"
                        style="white-space: pre-wrap; word-wrap: break-word"
                    ></pre>

                    <pre
                        ref="outputCodeStack"
                        v-if="showLog.stack"
                        v-text="showLog.stack"
                        class="block p-4 rounded-lg bg-gray-100 dark:bg-gray-900 w-full text-left"
                        style="white-space: pre-wrap; word-wrap: break-word"
                    ></pre>
                </div>

                <ModalFooter class="flex items-center justify-center">
                    <CancelButton
                        component="button"
                        type="button"
                        dusk="cancel-action-button"
                        @click.prevent="showLog = null"
                    />
                </ModalFooter>
            </div>
        </Modal>

        <DeleteResourceModal
            mode="delete"
            :show="deleteModalOpen"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        >
            <ModalHeader v-text="__('Delete Log file')" />
            <ModalContent>
                <p class="text-80 leading-normal">
                    {{ __("Are you sure you want to delete this ':fileName' file?", { fileName: this.file }) }}
                </p>
            </ModalContent>
        </DeleteResourceModal>
    </div>
</template>

<script>
    import api from '../api';
    import ToolbarButton from '../components/ToolbarButton'

    export default {
        components: {
            ToolbarButton,
        },

        data: () => ({
            loading: true,
            playing: false,
            interval: null,

            search: null,
            file: 'laravel.log',

            files: [],
            logs: {
                total: 0,
                per_page: 2,
                from: 1,
                to: 0,
                data: false,
                current_page: 1
            },

            deleteModalOpen: false,
            showLog: null,

            permissions: {},
            icons: {
                alert: 'bell',
                critical: 'shield-exclamation',
                debug: 'code',
                emergency: 'speakerphone',
                error: 'exclamation-circle',
                info: 'information-circle',
                notice: 'annotation',
                warning: 'exclamation',
            },
            colors: {
                alert: '#D32F2F',
                critical: '#F44336',
                debug: '#90CAF9',
                emergency: '#B71C1C',
                error: '#FF5722',
                info: '#1976D2',
                notice: '#4CAF50',
                warning: '#FF9100',
            },
        }),

        mounted() {
            this.setupInterval()
        },

        beforeUnmount() {
            clearInterval(this.interval)
        },

        async created() {
            document.addEventListener('keydown', this.handleKeydown);
            await this.getLogsPermissions();
            await this.getDailyLogFiles();
            await this.getLogs();
        },

        computed: {
            /**
             * Determine if prior pages are available.
             */
            hasPreviousPages: function () {
                return Boolean(this.logs && this.logs.prev_page_url);
            },

            /**
             * Determine if more pages are available.
             */
            hasMorePages: function () {
                return Boolean(this.logs && this.logs.next_page_url);
            }
        },

        methods: {
            handleKeydown(e) {
                if (e.code === 'Escape') {
                    this.showLog = null;
                }
            },

            download() {
                window.open(`/nova-vendor/stepanenko3/logs-tool/logs/${this.file}?time=${new Date().getTime()}`, '_parent');
            },

            getLogsPermissions() {
                return api.getLogsPermissions()
                    .then(permissions => this.permissions = permissions);
            },

            getDailyLogFiles() {
                return api.getDailyLogFiles().then(files => {
                    if (files.length) {
                        this.file = files[0];
                    }

                    this.files = files.map(val => ({ value: val, label: val }));
                });
            },

            refresh() {
                if (this.loading) return;

                this.getLogs(this.logs.current_page);
            },

            getLogs(page = 1) {
                this.loading = true;

                return this.fetch(page)
                    .finally(() => this.loading = false);
            },

            fetch(page = 1) {
                clearInterval(this.interval);

                return api.getLogs(this.file, page, this.search)
                    .then(logs => this.logs = logs)
                    .catch(() => {
                        this.logs.total = 0;
                        this.logs.from = 1;
                        this.logs.to = 0;
                        this.logs.data = false;
                        this.logs.current_page = 1;
                    })
                    .finally(() => this.setupInterval());
            },

            changeFile() {
                this.search = null;
                this.getLogs();
            },

            selectPreviousPage() {
                this.getLogs(this.logs.current_page - 1);
            },

            selectNextPage() {
                this.getLogs(this.logs.current_page + 1);
            },

            performSearch() {
                this.playing = false;
                this.$nextTick(() => this.fetch());
            },

            viewLog(log) {
                this.showLog = log;
            },

            openDeleteModal() {
                this.deleteModalOpen = true;
            },

            closeDeleteModal() {
                this.deleteModalOpen = false;
            },

            async confirmDelete() {
                this.deleteModalOpen = false;
                this.loading = true;

                await api.deleteFile(this.file);

                await this.getDailyLogFiles();
                await this.getLogs();
            },

            setupInterval() {
                this.interval = setInterval(() => {
                    if (this.playing && !this.loading) {
                        this.fetch();
                    }
                }, 5000);
            },
        }
    };
</script>

<style>
    table.sticky-last-column tr td:last-child {
        --tw-border-opacity: 1;
        background-color: rgba(var(--colors-gray-100),var(--tw-bg-opacity));
        right: 0;
        position: sticky;
        z-index: 10;
        width: 41px;
    }

    .dark table.sticky-last-column tr td:last-child {
        background-color: rgba(var(--colors-gray-700),var(--tw-border-opacity));
    }
</style>
