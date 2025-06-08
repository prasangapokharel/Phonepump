<div id="transactionSuccess" class="fixed inset-0 flex items-center justify-center bg-background-dark bg-opacity-95 z-50 hidden">
    <div class="bg-background-card p-6 rounded-xl shadow-lg max-w-md w-full">
        <div class="flex flex-col items-center">
            <div class="w-20 h-20 rounded-full bg-green-800 flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 4L12 14.01L9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2">Transaction Successful!</h2>
            <div class="w-full space-y-3 my-4">
                <div class="flex justify-between">
                    <span class="text-text-secondary">Amount:</span>
                    <span id="txAmount" class="font-medium">0 TRX</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">Transaction ID:</span>
                    <span id="txId" class="font-medium text-sm">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">Date:</span>
                    <span id="txDate" class="font-medium">-</span>
                </div>
            </div>
            <button onclick="closeTransactionSuccess()" class="bg-accent-yellow text-black px-6 py-2 rounded-lg hover:bg-opacity-80 transition w-full">Close</button>
        </div>
    </div>
</div>

<script>
    function showTransactionSuccess(data) {
        document.getElementById('txAmount').textContent = data.amount + ' TRX';
        document.getElementById('txId').textContent = data.txId;
        document.getElementById('txDate').textContent = data.date;
        document.getElementById('transactionSuccess').classList.remove('hidden');
    }
    
    function closeTransactionSuccess() {
        document.getElementById('transactionSuccess').classList.add('hidden');
        // Redirect to dashboard after closing
        window.location.href = 'dashboard.php';
    }
</script>
