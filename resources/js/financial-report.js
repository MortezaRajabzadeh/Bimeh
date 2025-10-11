/**
 * Financial Report Export Component
 * Alpine.js component for handling async financial report exports
 */

const financialReportExport = {
    downloading: false,
    exportJobId: null,
    exportStatus: null,
    progress: 0,
    pollInterval: null,

    /**
     * Start the export process
     */
    async startExport() {
        this.downloading = true;
        this.progress = 0;
        
        try {
            // Get CSRF token from meta tag
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch(window.exportRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    format: 'xlsx'
                })
            });
            
            if (!response.ok) {
                throw new Error(`خطا در سرور: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.export_job_id) {
                this.exportJobId = data.export_job_id;
                this.startPolling();
                console.log('🚀 Export شروع شد:', data.export_job_id);
            } else {
                throw new Error('Job ID دریافت نشد');
            }
            
        } catch (error) {
            console.error('خطا در شروع export:', error);
            this.downloading = false;
            alert('خطا در شروع export: ' + error.message);
        }
    },

    /**
     * Start polling for export status
     */
    startPolling() {
        this.pollInterval = setInterval(() => {
            this.checkStatus();
        }, 2000); // Poll every 2 seconds
    },

    /**
     * Check the current export status
     */
    async checkStatus() {
        try {
            const response = await fetch(
                `${window.statusRoute}?job_id=${this.exportJobId}`
            );
            
            if (!response.ok) {
                throw new Error(`خطا در بررسی وضعیت: ${response.status}`);
            }
            
            const status = await response.json();
            this.exportStatus = status.status;
            this.progress = status.progress;
            
            console.log('📊 وضعیت جدید:', status);
            
            if (status.status === 'completed') {
                console.log('✅ Export کامل شد!');
                this.cleanup();
                this.downloadFile();
            } else if (status.status === 'failed') {
                console.error('❌ Export نافرجام:', status.error);
                this.cleanup();
                alert('خطا در تولید گزارش: ' + (status.error || 'خطای نامشخص'));
            }
            
        } catch (error) {
            console.error('خطا در بررسی وضعیت:', error);
            // Don't stop polling on network errors - give it a chance to recover
        }
    },

    /**
     * Download the generated file
     */
    downloadFile() {
        const downloadUrl = window.downloadRoute.replace('__JOB_ID__', this.exportJobId);
        
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = '';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        console.log('📁 فایل دانلود شد');
        
        // Reset state
        this.exportJobId = null;
        this.exportStatus = null;
        this.progress = 0;
    },

    /**
     * Clean up polling interval
     */
    cleanup() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.downloading = false;
    },

    /**
     * Initialize component (if needed)
     */
    init() {
        // Any initialization logic
        console.log('💡 Financial Report Export component initialized');
    }
};

// Make available globally
window.financialReportExport = financialReportExport;

// Export as ES6 module as well
export default financialReportExport;