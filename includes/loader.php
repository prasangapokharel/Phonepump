<div id="loader" class="fixed inset-0 flex items-center justify-center bg-primary-950/80 backdrop-blur-sm z-50 hidden">
    <div class="glass rounded-3xl p-8 flex flex-col items-center space-y-4">
        <div class="relative">
            <div class="w-12 h-12 border-4 border-primary-700 rounded-full"></div>
            <div class="w-12 h-12 border-4 border-accent-yellow border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
        </div>
        <p class="text-primary-300 font-medium">Processing...</p>
    </div>
</div>

<script>
    function showLoader() {
        document.getElementById('loader').classList.remove('hidden');
    }
    
    function hideLoader() {
        document.getElementById('loader').classList.add('hidden');
    }
</script>
