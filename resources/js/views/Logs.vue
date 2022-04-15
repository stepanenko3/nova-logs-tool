<template>
    <div>
        <Heading class="mb-3"> {{ __('Logs') }}</Heading>

        <div class="grid gap-6 md:grid-cols-12 mb-4">
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
                    :aria-label="__('Search')"
                    type="search"
                    @input="search = $event.target.value"
                    @keydown.stop="performSearch"
                />
            </div>

            <div class="md:col-span-4 hidden md:flex"></div>

            <div v-if="!loading && files.length" class="md:col-span-4 md:justify-end flex items-center">
                <button
                    v-if="permissions.canDownload"
                    @click.prevent="download"
                    :title="__('Download')"
                    class="cursor-pointer text-70 hover:text-primary mr-3"
                >
                    <Icon type="download" view-box="0 0 24 24" width="24" height="24" />
                </button>
                <button
                    v-if="permissions.canDelete"
                    :title="__('Delete')"
                    class="cursor-pointer text-70 hover:text-primary mr-3"
                    @click.prevent="openDeleteModal"
                >
                    <Icon type="trash" view-box="0 0 24 24" width="20" height="20" />
                </button>

                <SelectControl
                    size="lg"
                    :options="files"
                    v-model:selected="file"
                    @input="file = $event.target.value"
                    @change="changeFile"
                />
            </div>
        </div>

        <div class="relative" :class="{ 'overflow-hidden': loading }">
            <div v-if="loading" class="flex items-center justify-center z-50 p-6" style="min-height: 150px">
                <Loader class="text-60" />
            </div>
            <template v-else>
                <Card>
                    <div class="overflow-hidden overflow-x-auto relative">
                        <table v-if="logs.data.length > 0" class="w-full table-default">
                            <thead>
                                <tr>
                                    <th class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Level') }}
                                    </th>
                                    <th class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Created at') }}
                                    </th>
                                    <th class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Message') }}
                                    </th>
                                    <th class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"></th>
                                </tr>
                            </thead>

                            <tbody v-for="(log, index) in logs.data">
                                <tr class="hover:bg-blue-lightest">
                                    <td class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        <span class="whitespace-no-wrap flex items-center">
                                            <Icon
                                                :type="icons[log.level || 'error']"
                                                view-box="0 0 24 24"
                                                width="24"
                                                height="24"
                                                :style="{ color: colors[log.level || 'error'] }"
                                            />
                                            <span class="ml-2">{{ log.level }}</span>
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        {{ log.date }}
                                    </td>
                                    <td class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        {{ log.text }}
                                    </td>

                                    <td class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 text-right">
                                        <span @click="viewLog(log)">
                                            <Icon type="eye" view-box="0 0 24 24" width="24" height="24" />
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!logs.data.length" class="flex justify-center items-center px-6 py-8">
                        <div class="text-center">
                            <icon type="search" class="mb-3" width="50" height="50" style="color: #A8B9C5"></icon>

                            <h3 class="text-base text-80 font-normal mb-6">
                                {{ __('No Logs.') }}
                            </h3>
                        </div>
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
                                @click.prevent="selectPreviousPage()"
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

                        <span class="ml-2">{{ showLog.level.toUpperCase() }}</span>
                    </Heading>

                    <div class="mt-3 text-center">{{ showLog.date }}</div>

                    <pre class="block w-full text-left" style="margin:16px 0 0 0"><code class="language-bash"
                        style="white-space: pre-wrap"
                        ref="outputCodeMessage"
                        v-text="'[message]\n' + showLog.text"
                    ></code></pre>
                    <pre class="block w-full text-left" style="margin:16px 0 0 0"><code class="language-bash"
                        style="white-space: pre-wrap"
                        ref="outputCodeStack"
                        v-text="showLog.stack"
                    ></code></pre>
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
                    {{
                        __("Are you sure you want to delete this ':fileName' file?", {
                            fileName: this.file
                        })
                    }}
                </p>
            </ModalContent>
        </DeleteResourceModal>
    </div>
</template>

<script>
import api from '../api';
import Prism from 'prismjs/components/prism-core';
import 'prismjs/components/prism-bash';

export default {
    data() {
        return {
            deleteModalOpen: false,
            search: null,
            loading: true,
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
        };
    },
    mounted() {},
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
        hasPreviousPages: function() {
            return Boolean(this.logs && this.logs.prev_page_url);
        },

        /**
         * Determine if more pages are available.
         */
        hasMorePages: function() {
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
            return api.getLogsPermissions().then(permissions => {
                this.permissions = permissions;
            });
        },
        getDailyLogFiles() {
            return api.getDailyLogFiles().then(files => {
                if (files.length) {
                    this.file = files[0];
                }

                this.files = files.map(val => ({ value: val, label: val }));
            });
        },
        getLogs(page = 1) {
            this.loading = true;

            return api.getLogs(this.file, page, this.search).then(logs => {
                this.logs = logs;

                this.loading = false;
            });
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
            this.$nextTick(() => {
                this.getLogs();
            });
        },
        viewLog(log) {
            this.showLog = log;
            this.$nextTick(() => {
                Prism.highlightElement(this.$refs.outputCodeMessage);
                Prism.highlightElement(this.$refs.outputCodeStack);
            });
        },
        openDeleteModal() {
            this.deleteModalOpen = true;
        },
        closeDeleteModal() {
            this.deleteModalOpen = false;
        },
        async confirmDelete() {
            this.deleteModalOpen = false;
            await api.deleteFile(this.file);

            await this.getDailyLogFiles();
            await this.getLogs();
        }
    }
};
</script>

<style src="prismjs/themes/prism.css" />
