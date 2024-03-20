<template>
    <div class="nova-logs-tool">
        <div class="text-lg font-bold mb-3">
            {{ __("Logs") }}
        </div>

        <div
            class="flex flex-col md:flex-row mb-4 space-y-3 md:space-y-0 md:space-x-3"
            v-if="files?.length > 0"
            :class="{
                'pointer-events-none opacity-50': loading,
            }"
        >
            <input
                class="appearance-none bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-800 h-10 px-3 rounded-lg md:flex-grow"
                :placeholder="__('Search')"
                spellcheck="false"
                :aria-label="__('Search')"
                type="text"
                @input="performSearch"
            />
            <select
                class="appearance-none bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-800 h-10 px-3 rounded-lg"
                :value="file"
                @change="(e) => changeFile(e.target.value)"
            >
                <option
                    v-for="signleFile in files"
                    :key="signleFile.name"
                    class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                    :value="signleFile.name"
                >
                    {{ signleFile.name }}

                    ({{ signleFile.sizeFormatted }})
                </option>
            </select>

            <div
                class="inline-flex items-center shadow rounded-lg bg-white dark:bg-gray-800 px-2 h-10"
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
                    v-tooltip="
                        playing ? __('Stop polling') : __('Start polling')
                    "
                />

                <ToolbarButton
                    v-if="files.length && file && permissions.canDownload"
                    @click.prevent="download"
                    type="download"
                    v-tooltip="__('Download')"
                />

                <ToolbarButton
                    v-if="files.length && file && permissions.canDelete"
                    @click.prevent="() => (deleteModalOpen = true)"
                    type="trash"
                    v-tooltip="__('Delete')"
                />

                <ToolbarButton
                    v-if="files.length && file"
                    @click.prevent="cacheClear"
                    type="database"
                    v-tooltip="__('Cache Clear')"
                />
            </div>
        </div>

        <div
            v-if="files.length && levels.length"
            class="flex mb-4 overflow-y-hidden overflow-x-auto relative"
        >
            <template v-for="item in levels.filter((n) => n.count)">
                <Button
                    @click="toggleLevel(item.level.value)"
                    class="mr-2"
                    :style="{
                        'background-color': item.selected
                            ? colors[item.level.value || 'error']
                            : '',
                    }"
                    :type="!item.selected ? 'gray' : ''"
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
                </Button>
            </template>
        </div>

        <div class="relative" :class="{ 'overflow-hidden': loading }">
            <div
                v-if="loading"
                class="flex items-center justify-center z-50 p-6"
                style="min-height: 150px"
            >
                <Loader class="text-60" />
            </div>
            <template v-else>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="overflow-y-hidden overflow-x-auto relative">
                        <table
                            v-if="files.length && logs.data.length"
                            class="w-full table-default"
                        >
                            <thead>
                                <tr>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    ></th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    >
                                        {{ __("Level") }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    >
                                        {{ __("Time") }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    >
                                        {{ __("Env") }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2 w-full"
                                    >
                                        {{ __("Description") }}
                                    </th>
                                    <th
                                        class="text-left px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    >
                                        {{ __("Line") }}
                                    </th>
                                    <th
                                        class="text-center px-2 whitespace-nowrap uppercase text-gray-500 text-xxs tracking-wide py-2"
                                    ></th>
                                </tr>
                            </thead>

                            <tbody v-for="(log, index) in logs.data">
                                <tr class="hover:bg-blue-lightest">
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                    >
                                        <Icon
                                            :type="
                                                icons[
                                                    log.level.value || 'error'
                                                ]
                                            "
                                            view-box="0 0 24 24"
                                            width="24"
                                            height="24"
                                            :style="{
                                                color: colors[
                                                    log.level.value || 'error'
                                                ],
                                            }"
                                        />
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                        :style="{
                                            color: colors[
                                                log.level.value || 'error'
                                            ],
                                        }"
                                    >
                                        {{
                                            capitalizeFirstLetter(
                                                log.level.value
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 text-gray-900 dark:text-gray-400"
                                    >
                                        {{ formattedDateTime(log.time) }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                    >
                                        {{ log.environment }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 w-full truncate max-w-1 min-w-200"
                                    >
                                        {{ log.text }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
                                    >
                                        {{ log.index }}
                                    </td>
                                    <td
                                        class="px-2 py-2 border-t border-gray-100 dark:border-gray-700 whitespace-nowrap cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 text-right"
                                    >
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
                        v-if="!files.length || !logs.data.length"
                        class="flex flex-col justify-center items-center px-6 py-8"
                    >
                        <Icon
                            type="search"
                            class="mb-3 text-gray-300 dark:text-gray-500"
                            width="50"
                            height="50"
                        />

                        <h3 class="text-base font-normal mt-3">
                            {{ __("No Logs.") }}
                        </h3>
                    </div>

                    <div class="bg-20 rounded-b">
                        <nav
                            v-if="files.length && logs.data.length > 0"
                            class="flex justify-between"
                        >
                            <!-- Previous Link -->
                            <button
                                :disabled="logs.onFirstPage"
                                class="btn btn-link py-3 px-4"
                                :class="{
                                    'text-primary dim': !logs.onFirstPage,
                                    'text-80 opacity-50': logs.onFirstPage,
                                }"
                                rel="prev"
                                @click.prevent="getLogs(logs.prevPage)"
                                dusk="previous"
                            >
                                {{ __("Previous") }}
                            </button>

                            <div class="hidden md:flex">
                                <button
                                    class="btn btn-link py-3 px-3 text-50 dim"
                                    v-for="page in logs.elements"
                                    :class="{
                                        'text-primary dim': page.active,
                                        'text-80 opacity-50': !page.active,
                                    }"
                                    :disabled="
                                        page.page === logs.currentPage ||
                                        typeof page.page !== 'number'
                                    "
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
                                    'text-80 opacity-50': !logs.hasMorePages,
                                }"
                                rel="next"
                                @click.prevent="getLogs(logs.nextPage)"
                                dusk="next"
                            >
                                {{ __("Next") }}
                            </button>
                        </nav>
                    </div>
                </div>

                <div
                    v-if="memoryUsage && requestTime"
                    class="text-right px-4 mt-2"
                >
                    <p class="text-xs text-gray-400">
                        Memory:
                        <span class="font-semibold">{{ memoryUsage }}</span
                        >, Duration:
                        <span class="font-semibold">{{ requestTime }}</span>
                    </p>
                </div>
            </template>
        </div>

        <div
            class="fixed inset-0 z-[60] bg-gray-500/75 dark:bg-gray-900/75 flex items-center justify-center p-3"
            v-if="showLog"
        >
            <div
                class="absolute inset-0"
                @click.prevent="() => (showLog = false)"
            ></div>
            <div
                class="relative p-3 md:p-6 bg-white dark:bg-gray-800 rounded-lg shadow min-w-80 md:w-1/2 max-w-full max-h-full overflow-y-auto"
            >
                <div
                    class="absolute top-0 right-0 w-5 cursor-pointer hover:opacity-75 text-lg"
                    @click.prevent="() => (showLog = false)"
                >
                    &#x2715;
                </div>

                <div class="flex items-center text-lg font-bold mb-5">
                    <Icon
                        :type="icons[showLog.level.value || 'error']"
                        view-box="0 0 24 24"
                        width="32"
                        height="32"
                        :style="{
                            color: colors[showLog.level.value || 'error'],
                        }"
                    />

                    <span class="ml-2">
                        {{ showLog.level.value.toUpperCase() }}
                    </span>
                </div>

                <div class="mt-3 mb-4">
                    {{ showLog.environment }}
                </div>

                <div class="mt-3 mb-4">
                    {{ formattedDateTime(showLog.time) }}
                </div>

                <pre
                    v-if="showLog.fullText"
                    v-text="showLog.fullText"
                    class="block p-4 rounded-lg bg-gray-100 dark:bg-gray-900 w-full text-left"
                    style="white-space: pre-wrap; word-wrap: break-word"
                ></pre>

                <div class="flex items-center space-x-3 mt-5">
                    <Button
                        type="gray"
                        @click.prevent="() => (showLog = false)"
                    >
                        {{ __("Cancel") }}
                    </Button>
                </div>
            </div>
        </div>

        <div
            class="fixed inset-0 z-[60] bg-gray-500/75 dark:bg-gray-900/75 flex items-center justify-center p-3"
            v-if="deleteModalOpen"
        >
            <div
                class="absolute inset-0"
                @click.prevent="() => (deleteModalOpen = false)"
            ></div>
            <div
                class="relative p-6 bg-white dark:bg-gray-800 rounded-lg shadow min-w-80 md:w-1/2 max-w-full max-h-full overflow-y-auto"
            >
                <div
                    class="absolute top-0 right-0 w-5 cursor-pointer hover:opacity-75 text-lg"
                    @click.prevent="() => (deleteModalOpen = false)"
                >
                    &#x2715;
                </div>

                <div class="flex items-center text-lg font-bold mb-5">
                    {{ __("Delete Log file") }}
                </div>

                <p class="text-80 leading-normal">
                    {{
                        __(
                            "Are you sure you want to delete this ':fileName' file?",
                            { fileName: file ?? "-" }
                        )
                    }}
                </p>

                <div class="flex items-center space-x-3 mt-5">
                    <Button
                        type="gray"
                        @click.prevent="() => (deleteModalOpen = false)"
                    >
                        {{ __("Cancel") }}
                    </Button>
                    <Button type="danger" @click.prevent="confirmDelete">
                        {{ __("Confirm") }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import api from "../api";
import Button from "../components/Button.vue";
import ToolbarButton from "../components/ToolbarButton.vue";
import { DateTime } from "luxon";

const loading = ref(true);
const playing = ref(false);
const interval = ref();
const search = ref();
const file = ref();
const levels = ref([]);
const selectedLevels = ref(null);
const memoryUsage = ref();
const requestTime = ref();
const files = ref([]);
const deleteModalOpen = ref(false);
const showLog = ref();
const permissions = ref({});
const searchTimeout = ref();
const logs = ref();

const icons = {
    alert: "bell",
    critical: "shield-exclamation",
    debug: "code",
    emergency: "speakerphone",
    error: "exclamation-circle",
    info: "information-circle",
    notice: "annotation",
    warning: "exclamation",
};

const colors = {
    alert: "#D32F2F",
    critical: "#F44336",
    debug: "#90CAF9",
    emergency: "#B71C1C",
    error: "#FF5722",
    info: "#1976D2",
    notice: "#4CAF50",
    warning: "#FF9100",
};

onMounted(async () => {
    await getLogsPermissions();
    await getLogs();

    setupInterval();
});

onBeforeUnmount(() => clearInterval(interval.value));

function setupInterval() {
    interval.value = setInterval(() => {
        if (playing.value && !loading.value) {
            fetch();
        }
    }, 5000);
}

function getLogsPermissions() {
    return api
        .getLogsPermissions()
        .then((response) => (permissions.value = response));
}

function getLogs(page = 1) {
    loading.value = true;

    return fetch(page).finally(() => (loading.value = false));
}

function fetch(page = 1) {
    clearInterval(interval.value);

    return api
        .getLogs(file.value, page, search.value, selectedLevels.value)
        .then((data) => {
            files.value = data.files;

            if (data.file) {
                file.value = data.file.name;
                logs.value = data.file.logs;
                levels.value = Object.values(data.file.levels);

                if (selectedLevels.value === null) {
                    selectedLevels.value = [];
                    levels.value.map((n) => {
                        if (!n.selected || !n.count) return;

                        selectedLevels.value.push(n.level.value);
                    });
                }
                memoryUsage.value = data.file.memoryUsage;
                requestTime.value = data.file.requestTime;
            }
        })
        .catch(() => {
            files.value = [];

            file.value = "";
            logs.value = {
                total: 0,
                per_page: 0,
                from: 0,
                to: 0,
                data: [],
                current_page: 1,
            };
            levels.value = [];
            memoryUsage.value = "";
            requestTime.value = "";
        })
        .finally(() => setupInterval());
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function toggleLevel(level) {
    const index = selectedLevels.value.indexOf(level);

    if (index === -1) {
        selectedLevels.value.push(level);
    } else {
        selectedLevels.value.splice(index, 1);
    }

    getLogs();
}

function formattedDateTime(value) {
    return DateTime.fromISO(value)
        .setZone(Nova.config("userTimezone") || Nova.config("timezone"))
        .toLocaleString({
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            timeZoneName: "short",
        });
}

function download() {
    window.open(
        `/nova-vendor/stepanenko3/logs-tool/logs/${
            file.value
        }?time=${new Date().getTime()}`,
        "_parent"
    );
}

function changeFile(fileName) {
    file.value = fileName;
    search.value = null;
    selectedLevels.value = null;

    getLogs();
}

function performSearch(e) {
    search.value = e.target.value;
    playing.value = false;

    if (searchTimeout.value) clearTimeout(searchTimeout.value);

    searchTimeout.value = setTimeout(() => fetch(), 250);
}

function viewLog(log) {
    showLog.value = log;
}

async function cacheClear() {
    if (loading.value) return;

    await api.cacheClear(file.value);

    await getLogs();
}

async function confirmDelete() {
    loading.value = true;
    deleteModalOpen.value = false;

    return await api.deleteFile(file.value).finally(async () => {
        file.value = null;
        search.value = null;
        selectedLevels.value = null;

        return await getLogs().finally(() => (deleteModalOpen.value = false));
    });
}
</script>
