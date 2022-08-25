export default {
    getLogs(file = null, current_page = 1, search = null, selectedLevels = null) {
        const data = {};

        if (file) data.file = file;
        if (current_page) data.page = current_page;
        if (search) data.search = search;
        if (selectedLevels !== null) data.selectedLevels = [...selectedLevels];

        const query = new URLSearchParams(data);
        return Nova.request()
            .get(`/nova-vendor/stepanenko3/logs-tool/logs?${query}`)
            .then(response => response.data);
    },

    deleteFile(file) {
        return Nova.request()
            .delete(`/nova-vendor/stepanenko3/logs-tool/logs?file=${file}`)
            .then(response => response.data);
    },

    cacheClear(file = null) {
        return Nova.request()
            .delete(
                file
                    ? `/nova-vendor/stepanenko3/logs-tool/logs/cache?file=${file}`
                    : `/nova-vendor/stepanenko3/logs-tool/logs/cache`
            )
            .then(response => response.data);
    },

    getLogsPermissions(file) {
        return Nova.request()
            .get(`/nova-vendor/stepanenko3/logs-tool/logs/permissions`)
            .then(response => response.data);
    }
};
