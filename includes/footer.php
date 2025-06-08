<footer style="background: #111; border-top: 1px solid #333; padding: 40px 0; margin-top: 60px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 30px;">
            <div>
                <h3 style="color: #fff; margin-bottom: 16px; font-size: 16px;">TronPump</h3>
                <p style="color: #999; font-size: 14px; line-height: 1.5;">
                    Advanced token trading platform for TRC-20 tokens with bonding curve technology.
                </p>
            </div>
            
            <div>
                <h3 style="color: #fff; margin-bottom: 16px; font-size: 16px;">Quick Links</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="/" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Home</a>
                    <a href="/user/trade.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Trade</a>
                    <a href="/user/launch.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Launch</a>
                    <a href="/user/assets.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Assets</a>
                </div>
            </div>
            
            <div>
                <h3 style="color: #fff; margin-bottom: 16px; font-size: 16px;">Resources</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Documentation</a>
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">API</a>
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Support</a>
                </div>
            </div>
            
            <div>
                <h3 style="color: #fff; margin-bottom: 16px; font-size: 16px;">Legal</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Terms of Service</a>
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Privacy Policy</a>
                    <a href="#" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Disclaimer</a>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; color: #666; font-size: 14px; padding-top: 20px; border-top: 1px solid #333;">
            &copy; <?php echo date('Y'); ?> TronPump. All rights reserved.
        </div>
    </div>
</footer>

<style>
@media (max-width: 768px) {
    footer > div > div:first-child {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
    }
}
</style>
