// Secret Page Functionality
// This file contains the secret page that unlocks when typing the phone number in console

// Secret Page HTML Template
const secretPageHTML = `
<!-- Secret Page Modal -->
<div id="secretPageModal" class="modal">
  <div class="modal-content secret-modal">
    <div class="secret-header">
      <h2>🎉 Secret Page Unlocked! 🎉</h2>
      <div class="secret-subtitle">Welcome to the hidden realm</div>
    </div>

    <div class="secret-content">
      <div class="secret-message">
        <p>🕵️‍♂️ Congratulations! You've discovered the secret page!</p>
        <p>📱 You entered the correct phone number in the console.</p>
        <p>⭐ This is a special Easter egg just for you.</p>
      </div>

      <div class="secret-stats">
        <div class="stat-item">
          <span class="stat-icon">🔐</span>
          <span class="stat-text">Admin Access</span>
        </div>
        <div class="stat-item">
          <span class="stat-icon">📸</span>
          <span class="stat-text">Photo Gallery</span>
        </div>
        <div class="stat-item">
          <span class="stat-icon">🎯</span>
          <span class="stat-text">Secret Found</span>
        </div>
      </div>

      <div class="secret-actions">
        <button onclick="closeSecretPage()" class="secret-close-btn">Close Secret Page</button>
      </div>
    </div>
  </div>
</div>
`;

// Secret Page CSS Styles
const secretPageCSS = `
/* Secret Page Styles */
.secret-modal {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  max-width: 600px;
  text-align: center;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  transform: scale(0.8);
  transition: transform 0.3s ease;
}

.secret-header {
  background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
  padding: 30px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.secret-header h2 {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.secret-subtitle {
  margin: 10px 0 0;
  font-size: 16px;
  opacity: 0.9;
  color: rgba(255,255,255,0.8);
}

.secret-content {
  padding: 30px 20px;
}

.secret-message {
  margin-bottom: 30px;
}

.secret-message p {
  margin: 10px 0;
  font-size: 16px;
  line-height: 1.6;
}

.secret-stats {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-bottom: 30px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 15px;
  background: rgba(255,255,255,0.1);
  border-radius: 12px;
  min-width: 80px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

.stat-icon {
  font-size: 24px;
}

.stat-text {
  font-size: 12px;
  font-weight: 600;
  opacity: 0.9;
}

.secret-actions {
  margin-top: 20px;
}

.secret-close-btn {
  background: linear-gradient(135deg, #ff6b6b, #ee5a52);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.secret-close-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

.secret-close-btn:active {
  transform: translateY(0);
}

@media (max-width: 768px) {
  .secret-modal {
    margin: 20px;
    max-width: none;
  }

  .secret-stats {
    gap: 10px;
  }

  .stat-item {
    min-width: 60px;
    padding: 10px;
  }

  .secret-header h2 {
    font-size: 24px;
  }
}
`;

// Initialize Secret Page
function initSecretPage() {
  // Add HTML to page
  document.body.insertAdjacentHTML('beforeend', secretPageHTML);

  // Add CSS to page
  const style = document.createElement('style');
  style.textContent = secretPageCSS;
  document.head.appendChild(style);

  // Silent initialization for better security
}

// Secret Page Functions
function showSecretPage() {
  const modal = document.getElementById('secretPageModal');
  if (modal) {
    modal.style.display = 'flex';
    // Add some animation
    setTimeout(() => {
      const secretModal = modal.querySelector('.secret-modal');
      if (secretModal) {
        secretModal.style.transform = 'scale(1)';
      }
    }, 100);
  }
}

function closeSecretPage() {
  const modal = document.getElementById('secretPageModal');
  if (modal) {
    const secretModal = modal.querySelector('.secret-modal');
    if (secretModal) {
      secretModal.style.transform = 'scale(0.8)';
    }
    setTimeout(() => {
      modal.style.display = 'none';
    }, 300);
  }
}

// Secret Console Function
window.unlockSecret = function(code) {
  const secretCode = '0799102011';
  if (code === secretCode) {
    console.log('🎉 Secret code accepted! Redirecting to secret page...');
    setTimeout(() => {
      window.location.href = 'secret.php?code=' + secretCode;
    }, 500);
    return '🎉 Redirecting to secret page!';
  } else {
    console.log('❌ Incorrect code. Try again!');
    return '❌ Access denied';
  }
};

// Console Input Detection
(function() {
  const originalLog = console.log;

  console.log = function(...args) {
    // Check if any argument contains the secret code
    const secretCode = '0799102011';
    const hasSecret = args.some(arg =>
      typeof arg === 'string' && arg.trim() === secretCode
    );

    if (hasSecret) {
      console.log('🎉 Secret code detected! Redirecting to secret page...');
      setTimeout(() => {
        window.location.href = 'secret.php?code=' + secretCode;
      }, 500);
    }

    originalLog.apply(console, args);
  };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSecretPage);
} else {
  initSecretPage();
}