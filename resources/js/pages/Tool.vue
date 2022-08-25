<template>
    <div>
        <Heading class="mb-3"> {{ __('Logs') }}</Heading>

        <div
            v-if="files.length > 0"
            class="grid gap-2 md:gap-6 md:grid-cols-12 mb-4"
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
                    :aria-label="__('Search')"
                    type="text"
                    @input="performSearch"
                />
            </div>

            <div class="md:col-span-4 hidden md:flex"></div>

            <div
                v-if="files.length"
                class="md:col-span-4 md:justify-end flex items-center flex-wrap"
                :class="{ disabled: loading }"
            >
                <Dropdown class="hover:bg-gray-100 dark:hover:bg-gray-600 rounded">
                    <DropdownTrigger class="toolbar-button whitespace-nowrap px-2">
                        {{ file }}
                    </DropdownTrigger>

                    <template #menu>
                        <DropdownMenu
                            class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid"
                            width="auto"
                        >
                            <DropdownMenuItem
                                v-for="signleFile in files"
                                :key="signleFile.name"
                                :onclick="(() => changeFile(signleFile.name))"
                                class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                                :class="{ active: signleFile.name === file }"
                            >
                                {{ signleFile.name }}

                                <b>
                                    {{ signleFile.sizeFormatted }}
                                </b>
                            </DropdownMenuItem>
                        </DropdownMenu>
                    </template>
                </Dropdown>

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
                        v-if="file && permissions.canDownload"
                        @click.prevent="download"
                        type="download"
                        v-tooltip="__('Download')"
                    />

                    <ToolbarButton
                        v-if="file && permissions.canDelete"
                        @click.prevent="openDeleteModal"
                        type="trash"
                        v-tooltip="__('Delete')"
                    />

                    <ToolbarButton
                        v-if="file"
                        @click.prevent="cacheClear"
                        type="database"
                        v-tooltip="__('Cache Clear')"
                    />
                </div>
            </div>
        </div>

        <div v-if="levels.length" class="flex mb-4 overflow-y-hidden overflow-x-auto relative">
            <template
                v-for="item in (levels.filter(n => n.count))"
            >
                <DefaultButton
                    v-if="item.selected"
                    @click="toggleLevel(item.level.value)"
                    class="mr-2"
                    :style="{
                        'background-color': colors[item.level.value || 'error'],
                        'border': 'none',
                        'color': '#fff'
                    }"
                >
                    <Icon
                        :type="icons[item.level.value || 'error']"
                        view-box="0 0 24 24"
                        width="18"
                        height="18"
                        class="mr-1"
                    />

                    {{ capitalizeFirstLetter(item.level.value) }}:

                    <span class="text-xs opacity-75 ml-1">
                        {{ item.count }}
                    </span>
                </DefaultButton>

                <OutlineButton
                    v-else
                    @click="toggleLevel(item.level.value)"
                    class="mr-2"
                >
                    <Icon
                        :type="icons[item.level.value || 'error']"
                        view-box="0 0 24 24"
                        width="18"
                        height="18"
                        class="mr-1"
                    />

                    {{ capitalizeFirstLetter(item.level.value) }}:

                    <span class="text-xs opacity-75 ml-1">
                        {{ item.count }}
                    </span>
                </OutlineButton>
            </template>
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
                        <table v-if="logs.data.length > 0" class="w-full table-default">
                            <thead>
                                <tr>
                                    <th class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"></th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Level') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Time') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Env') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2 w-full">
                                        {{ __('Description') }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                        {{ __('Line') }}
                                    </th>
                                    <th
                                        class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2">
                                    </th>
                                </tr>
                            </thead>

                            <tbody v-for="(log, index) in logs.data">
                                <tr class="hover:bg-blue-lightest">
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                    >
                                        <Icon
                                            :type="icons[log.level.value || 'error']"
                                            view-box="0 0 24 24"
                                            width="24"
                                            height="24"
                                            :style="{ color: colors[log.level.value || 'error'] }"
                                        />
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                        :style="{ color: colors[log.level.value || 'error'] }"
                                    >
                                        {{ capitalizeFirstLetter(log.level.value) }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 text-gray-900 dark:text-gray-400">
                                        {{ formattedDateTime(log.time) }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                    >
                                        {{ log.environment }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 w-full truncate max-w-1 min-w-200">
                                        {{ log.text }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
                                        {{ log.index }}
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
                                :disabled="logs.onFirstPage"
                                class="btn btn-link py-3 px-4"
                                :class="{
                                    'text-primary dim': !logs.onFirstPage,
                                    'text-80 opacity-50': logs.onFirstPage
                                }"
                                rel="prev"
                                @click.prevent="getLogs(logs.prevPage)"
                                dusk="previous"
                            >
                                {{ __('Previous') }}
                            </button>

                            <div class="hidden md:flex">
                                <button
                                    class="btn btn-link py-3 px-3 text-50 dim"
                                    v-for="page in logs.elements"
                                    :class="{
                                        'text-primary dim': page.active,
                                        'text-80 opacity-50': !page.active
                                    }"
                                    :disabled="page.page === logs.currentPage || typeof page.page !== 'number'"
                                    @click.prevent="getLogs(page.page)"
                                >
                                    {{ page.page }}
                                </button>
                            </div>

                            <!-- Next Link -->
                            <button
                                :disabled="!logs.hasMorePages"
                                class="btn btn-link py-3 px-4"
                                :class="{
                                    'text-primary dim': logs.hasMorePages,
                                    'text-80 opacity-50': !logs.hasMorePages
                                }"
                                rel="next"
                                @click.prevent="getLogs(logs.nextPage)"
                                dusk="next"
                            >
                                {{ __('Next') }}
                            </button>
                        </nav>
                    </div>
                </Card>

                <div
                    v-if="memoryUsage && requestTime"
                    class="text-right px-4 mt-2"
                >
                    <p class="text-xs text-gray-400">
                        Memory: <span class="font-semibold">{{ memoryUsage }}</span>,
                        Duration: <span class="font-semibold">{{ requestTime }}</span>
                    </p>
                </div>

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
                            :type="icons[showLog.level.value || 'error']"
                            view-box="0 0 24 24"
                            width="32"
                            height="32"
                            :style="{ color: colors[showLog.level.value || 'error'] }"
                        />

                        <span class="ml-2">
                            {{ showLog.level.value.toUpperCase() }}
                        </span>
                    </Heading>

                    <div class="mt-3 mb-4 text-center">
                        {{ showLog.environment }}
                    </div>

                    <div class="mt-3 mb-4 text-center">
                        {{ formattedDateTime(showLog.time) }}
                    </div>

                    <pre
                        ref="outputCodeStack"
                        v-if="showLog.fullText"
                        v-text="showLog.fullText"
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
    import { DateTime } from 'luxon'

    export default {
        components: {
            ToolbarButton,
        },

        data: () => ({
            loading: true,
            playing: false,
            interval: null,

            search: null,
            file: '',

            levels: [],
            selectedLevels: null,

            memoryUsage: null,
            requestTime: null,

            logs: {
                total: 0,
                per_page: 2,
                from: 1,
                to: 0,
                data: false,
                current_page: 1,
            },

            files: [],

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

            searchTimeout: null,
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
            await this.getLogs();
        },

        methods: {
            capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            },

            toggleLevel(level) {
                const index = this.selectedLevels.indexOf(level);

                if (index === -1) {
                    this.selectedLevels.push(level);
                } else {
                    this.selectedLevels.splice(index, 1);
                }

                this.getLogs();
            },

            formattedDateTime(value) {
                return DateTime.fromISO(value)
                    .setZone(Nova.config('userTimezone') || Nova.config('timezone'))
                    .toLocaleString({
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        timeZoneName: 'short',
                    });
            },

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

            refresh() {
                if (this.loading) return;

                this.getLogs(this.logs.currentPage);
            },

            getLogs(page = 1) {
                this.loading = true;

                return this.fetch(page)
                    .finally(() => this.loading = false);
            },

            fetch(page = 1) {
                clearInterval(this.interval);

                return api.getLogs(
                    this.file,
                    page,
                    this.search,
                    this.selectedLevels,
                )
                    .then(data => {
                        this.files = data.files;

                        if (data.file) {
                            this.file = data.file.name;
                            this.logs = data.file.logs;
                            this.levels = Object.values(data.file.levels);
                            if (this.selectedLevels === null) {
                                this.selectedLevels = [];
                                this.levels.map(n => {
                                    if (!n.selected || !n.count)
                                        return;

                                    this.selectedLevels.push(n.level.value);
                                });
                            }
                            this.memoryUsage = data.file.memoryUsage;
                            this.requestTime = data.file.requestTime;
                        }
                    })
                    .catch(() => {
                        this.files = [];

                        this.file = '';
                        this.logs =  {
                            total: 0,
                            per_page: 0,
                            from: 0,
                            to: 0,
                            data: [],
                            current_page: 1,
                        };
                        this.levels = [];
                        this.memoryUsage = '';
                        this.requestTime = '';
                    })
                    .finally(() => this.setupInterval());
            },

            changeFile(fileName) {
                this.file = fileName;
                this.search = null;
                this.selectedLevels = null;

                this.getLogs();
            },

            performSearch(e) {
                this.search = e.target.value;
                this.playing = false;

                if (this.searchTimeout) clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.fetch(), 250);
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

            async cacheClear() {
                if (this.loading) return;

                await api.cacheClear(this.file);

                await this.getLogs();
            },

            async confirmDelete() {
                this.deleteModalOpen = false;
                this.loading = true;

                await api.deleteFile(this.file);

                this.file = null;
                this.search = null;
                this.selectedLevels = null;

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
    .truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .max-w-1 {
        max-width: 1px;
    }
    .min-w-200 {
        min-width: 200px;
    }
</style>
