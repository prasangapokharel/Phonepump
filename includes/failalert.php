<div id="failAlert" class="fixed top-6 right-6 z-50 transform translate-x-full transition-transform duration-500 ease-out">
    <div class="glass border-accent-red/20 bg-accent-red/10 p-4 rounded-2xl shadow-xl max-w-sm">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-accent-red/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-accent-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div>
                <p id="failMessage" class="text-accent-red font-medium text-sm">Operation failed!</p>
            </div>
        </div>
    </div>
</div>

<script>
    function showFail(message) {
        const alert = document.getElementById('failAlert');
        const messageEl = document.getElementById('failMessage');
        
        messageEl.textContent = message || 'Operation failed!';
        alert.classList.remove('translate-x-full');
        
        setTimeout(() => {
            alert.classList.add('translate-x-full');
        }, 4000);
    }
</script>
