import Tool from './pages/Tool.vue'
import '../css/tool.css'

Nova.booting((Vue, router, store) => {
    Nova.inertia('NovaLogs', Tool);
});
