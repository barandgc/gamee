// Game+ Party Hub - Ana JavaScript Dosyası

// Global değişkenler
let currentUser = null;
let selectedGames = [];
let allGames = [];
let currentParty = null;

// API Base URL
const API_BASE = 'api/';

// Auth Tab Yönetimi
function showLogin() {
    document.querySelector('.auth-tab.active').classList.remove('active');
    event.target.classList.add('active');
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registerForm').style.display = 'none';
}

function showRegister() {
    document.querySelector('.auth-tab.active').classList.remove('active');
    event.target.classList.add('active');
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
}

// Giriş Yapma
function loginUser(gamerTag) {
    fetch(API_BASE + 'users.php?gamer_tag=' + encodeURIComponent(gamerTag))
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user) {
            currentUser = data.user;
            localStorage.setItem('gameHub_user', JSON.stringify(currentUser));
            showMainApp();
            showAlert('Başarıyla giriş yapıldı!', 'success');
        } else {
            showAlert('Bu gamer tag ile kullanıcı bulunamadı! Lütfen kayıt olun.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bağlantı hatası!', 'danger');
    });
}

// Kayıt Olma
function registerUser(name, gamerTag) {
    fetch(API_BASE + 'users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'create_or_get',
            name: name,
            gamer_tag: gamerTag
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentUser = data.user;
            localStorage.setItem('gameHub_user', JSON.stringify(currentUser));
            showMainApp();
            showAlert('Hesabınız oluşturuldu ve giriş yapıldı!', 'success');
        } else {
            showAlert(data.error || 'Kullanıcı oluşturulurken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bağlantı hatası!', 'danger');
    });
}

// Ana uygulamayı göster
function showMainApp() {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('mainApp').style.display = 'block';
    updateUserDisplay();
    loadGames();
    loadParties();
}

// Giriş sayfasını göster
function showLoginPage() {
    document.getElementById('loginPage').style.display = 'block';
    document.getElementById('mainApp').style.display = 'none';
}

function checkUser() {
    const savedUser = localStorage.getItem('gameHub_user');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        showMainApp();
    } else {
        showLoginPage();
    }
}

function updateUserDisplay() {
    if (currentUser) {
        document.getElementById('currentUserName').textContent = currentUser.name;
        document.getElementById('currentGamerTag').textContent = currentUser.gamer_tag;
    }
}

function logoutUser() {
    currentUser = null;
    localStorage.removeItem('gameHub_user');
    showLoginPage();
    showAlert('Başarıyla çıkış yapıldı!', 'info');
}

// Alert Sistemi
function showAlert(message, type = 'info') {
    // Hangi alert area'yı kullanacağımızı belirle
    let alertArea = document.getElementById('alertArea');
    if (document.getElementById('mainApp').style.display !== 'none') {
        alertArea = document.getElementById('alertAreaMain');
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        ${message}
        <button style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;" onclick="this.parentElement.remove()">&times;</button>
    `;
    alertArea.appendChild(alertDiv);
    
    // 5 saniye sonra otomatik kaldır
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Popüler oyunları yükle
function loadPopularGames() {
    fetch(API_BASE + 'games.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPopularGames(data.games.slice(0, 8)); // İlk 8 oyunu göster
        }
    })
    .catch(error => {
        console.error('Error loading popular games:', error);
    });
}

function displayPopularGames(games) {
    const container = document.getElementById('popularGames');
    container.innerHTML = '';
    
    games.forEach(game => {
        const gameCard = document.createElement('div');
        gameCard.className = 'popular-game-card';
        gameCard.innerHTML = `
            <img src="images/games/${game.image}" alt="${game.title}" onerror="this.src='images/default-game.png'">
            <h5>${game.title}</h5>
            <div class="genre">${game.genre}</div>
        `;
        container.appendChild(gameCard);
    });
}

// Modal Yönetimi
function openModal(modalId) {
    if (modalId === 'createPartyModal' && !currentUser) {
        showAlert('Parti oluşturmak için önce kullanıcı bilgilerinizi girin!', 'warning');
        return;
    }
    
    // Body scroll'unu engelle
    document.body.style.overflow = 'hidden';
    
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    // Body scroll'unu geri aç
    document.body.style.overflow = 'auto';
    
    document.getElementById(modalId).style.display = 'none';
}

// Modal dışına tıklayınca kapat
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        // Body scroll'unu geri aç
        document.body.style.overflow = 'auto';
        event.target.style.display = 'none';
    }
}

// Oyun Yönetimi
function loadGames() {
    fetch(API_BASE + 'games.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allGames = data.games;
            displayGamesForSelection();
        } else {
            showAlert('Oyunlar yüklenirken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error loading games:', error);
        showAlert('Oyunlar yüklenirken bağlantı hatası!', 'danger');
    });
}

function displayGamesForSelection() {
    const gameSelection = document.getElementById('gameSelection');
    gameSelection.innerHTML = '';
    
    allGames.forEach(game => {
        const gameCard = document.createElement('div');
        gameCard.className = 'game-card';
        gameCard.onclick = () => toggleGameSelection(game.game_id, gameCard);
        gameCard.innerHTML = `
            <img src="images/games/${game.image}" alt="${game.title}" onerror="this.src='images/default-game.png'">
            <h4>${game.title}</h4>
            <p>${game.genre}</p>
            <small>Max ${game.max_players} oyuncu</small>
        `;
        gameSelection.appendChild(gameCard);
    });
}

// Yatay oyun listesi kaydırma kontrolleri
function scrollGamesLeft() {
    const container = document.getElementById('gameSelection');
    if (!container) return;
    container.scrollBy({ left: -Math.max(300, container.clientWidth * 0.8), behavior: 'smooth' });
}

function scrollGamesRight() {
    const container = document.getElementById('gameSelection');
    if (!container) return;
    container.scrollBy({ left: Math.max(300, container.clientWidth * 0.8), behavior: 'smooth' });
}

// Time Slot Management
let addedSlots = [];


function updateSlotsList() {
    const container = document.getElementById('addedSlotsList');
    
    if (addedSlots.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Henüz slot eklenmedi</div>';
        return;
    }
    
    // Basit HTML oluştur
    let html = '<div style="background: white; padding: 20px; border-radius: 10px; margin-top: 10px;">';
    html += '<h5>Eklenen Slotlar:</h5>';
    
    addedSlots.forEach(slot => {
        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin: 5px 0; border-radius: 5px;">
                <div>
                    <strong>Slot ${slot.id}</strong> - ${slot.start_time} - ${slot.end_time} 
                    <span style="color: #666;">(${calculateDuration(slot.start_time, slot.end_time)})</span>
                </div>
                <button class="btn btn-danger btn-sm" onclick="removeTimeSlot(${slot.id})">
                    <i class="fas fa-trash"></i> Sil
                </button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function calculateDuration(startTime, endTime) {
    const start = new Date(`2000-01-01T${startTime}`);
    const end = new Date(`2000-01-01T${endTime}`);
    const diffMs = end - start;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    return `${diffHours}s ${diffMinutes}dk`;
}


function toggleGameSelection(gameId, cardElement) {
    const index = selectedGames.indexOf(gameId);
    
    if (index > -1) {
        // Oyunu seçimden çıkar
        selectedGames.splice(index, 1);
        cardElement.classList.remove('selected');
    } else {
        // Oyunu seçime ekle (max 5 oyun)
        if (selectedGames.length >= 5) {
            showAlert('En fazla 5 oyun seçebilirsiniz!', 'warning');
            return;
        }
        selectedGames.push(gameId);
        cardElement.classList.add('selected');
    }
}

// Parti Yönetimi
function loadParties() {
    const loadingSpinner = document.getElementById('loadingSpinner');
    loadingSpinner.style.display = 'inline-block';
    
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    let url = API_BASE + 'parties.php';
    const params = new URLSearchParams();
    
    if (statusFilter) params.append('status', statusFilter);
    if (dateFilter) params.append('date', dateFilter);
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        loadingSpinner.style.display = 'none';
        if (data.success) {
            displayParties(data.parties);
        } else {
            showAlert('Partiler yüklenirken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        loadingSpinner.style.display = 'none';
        console.error('Error loading parties:', error);
        showAlert('Partiler yüklenirken bağlantı hatası!', 'danger');
    });
}

function displayParties(parties) {
    const partyList = document.getElementById('partyList');
    partyList.innerHTML = '';
    
    if (parties.length === 0) {
        partyList.innerHTML = '<div class="alert alert-info">Henüz parti bulunmuyor. İlk partiyi siz oluşturun!</div>';
        return;
    }
    
    parties.forEach(party => {
        const partyCard = document.createElement('div');
        partyCard.className = 'party-card';
        
        const statusClass = `status-${party.status}`;
        const statusText = getStatusText(party.status);
        
        partyCard.innerHTML = `
            <div class="party-status ${statusClass}">${statusText}</div>
            <h3>${party.title}</h3>
            <p><i class="fas fa-calendar"></i> ${formatDate(party.party_date)}</p>
            <p><i class="fas fa-user"></i> Host: ${party.host_name}</p>
            <p><i class="fas fa-users"></i> Katılımcı: ${party.participant_count}</p>
            <p><i class="fas fa-key"></i> Kod: <strong>${party.invite_code}</strong></p>
            ${party.selected_game_title ? `<p><i class="fas fa-gamepad"></i> Oyun: ${party.selected_game_title}</p>` : ''}
            <div style="margin-top: 15px;">
                <button class="btn" onclick="viewPartyDetails(${party.party_id})">
                    <i class="fas fa-eye"></i> Detaylar
                </button>
                ${currentUser && !isParticipant(party) ? 
                    `<button class="btn btn-success" onclick="joinPartyById(${party.party_id})">
                        <i class="fas fa-sign-in-alt"></i> Katıl
                    </button>` : ''}
            </div>
        `;
        
        partyList.appendChild(partyCard);
    });
}

function getStatusText(status) {
    const statusTexts = {
        planning: 'Planlama',
        voting: 'Oylama',
        scheduled: 'Planlandı',
        completed: 'Tamamlandı'
    };
    return statusTexts[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

function isParticipant(party) {
    // Bu fonksiyon geliştirilecek - katılımcı kontrolü
    return false;
}

// Parti Oluşturma
document.getElementById('createPartyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentUser) {
        showAlert('Parti oluşturmak için önce giriş yapın!', 'warning');
        return;
    }
    
    if (selectedGames.length < 3) {
        showAlert('En az 3 oyun seçmelisiniz!', 'warning');
        return;
    }
    
    const title = document.getElementById('partyTitle').value;
    const date = document.getElementById('partyDate').value;
    
    // Collect time slots from addedSlots array
    const timeSlots = addedSlots.map(slot => ({
        start_time: slot.start_time,
        end_time: slot.end_time
    }));
    
    const partyData = {
        action: 'create',
        title: title,
        date: date,
        host_id: currentUser.user_id,
        game_ids: selectedGames,
        time_slots: timeSlots
    };
    
    fetch(API_BASE + 'parties.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(partyData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`Parti başarıyla oluşturuldu! Davet kodu: ${data.invite_code}`, 'success');
            closeModal('createPartyModal');
            document.getElementById('createPartyForm').reset();
            selectedGames = [];
            addedSlots = [];
            updateSlotsList();
            loadParties();
            
            // Seçili oyunları temizle
            document.querySelectorAll('.game-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
        } else {
            showAlert(data.error || 'Parti oluşturulurken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error creating party:', error);
        showAlert('Parti oluşturulurken bağlantı hatası!', 'danger');
    });
});

// Partiye Katılma
function joinPartyByCode() {
    if (!currentUser) {
        showAlert('Partiye katılmak için önce giriş yapın!', 'warning');
        return;
    }
    openModal('joinCodeModal');
}

function joinParty() {
    const inviteCode = document.getElementById('inviteCode').value.trim().toUpperCase();
    
    if (!inviteCode) {
        showAlert('Lütfen davet kodunu girin!', 'warning');
        return;
    }
    
    fetch(API_BASE + 'parties.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'join',
            invite_code: inviteCode,
            user_id: currentUser.user_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Partiye başarıyla katıldınız!', 'success');
            closeModal('joinCodeModal');
            document.getElementById('inviteCode').value = '';
            loadParties();
        } else {
            showAlert(data.error || 'Partiye katılırken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error joining party:', error);
        showAlert('Partiye katılırken bağlantı hatası!', 'danger');
    });
}

function joinPartyById(partyId) {
    if (!currentUser) {
        showAlert('Partiye katılmak için önce giriş yapın!', 'warning');
        return;
    }
    
    fetch(API_BASE + 'parties.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'join_by_id',
            party_id: partyId,
            user_id: currentUser.user_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Partiye başarıyla katıldınız!', 'success');
            loadParties();
        } else {
            showAlert(data.error || 'Partiye katılırken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error joining party:', error);
        showAlert('Partiye katılırken bağlantı hatası!', 'danger');
    });
}

// Parti Detayları
function viewPartyDetails(partyId) {
    const userId = currentUser ? currentUser.user_id : 0;
    fetch(API_BASE + `party_details.php?party_id=${partyId}&user_id=${userId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPartyDetails(data);
            openModal('partyDetailModal');
        } else {
            showAlert('Parti detayları yüklenirken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error loading party details:', error);
        showAlert('Parti detayları yüklenirken bağlantı hatası!', 'danger');
    });
}

function displayPartyDetails(data) {
    const party = data.party;
    const games = data.games;
    const slots = data.slots;
    const participants = data.participants;
    
    let detailsHTML = `
        <h2>${party.title}</h2>
        <div class="party-status status-${party.status}">${getStatusText(party.status)}</div>
        <p><strong>Tarih:</strong> ${formatDate(party.party_date)}</p>
        <p><strong>Host:</strong> ${party.host_name}</p>
        <p><strong>Davet Kodu:</strong> ${party.invite_code}</p>
        <p><strong>Katılımcı Sayısı:</strong> ${participants.length}</p>
    `;
    
    // Katılımcılar
    if (participants.length > 0) {
        detailsHTML += `
            <h4>Katılımcılar:</h4>
            <div class="participants-list">
        `;
        participants.forEach(participant => {
            detailsHTML += `
                <div style="display: inline-block; margin: 5px; padding: 8px 12px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-user"></i> ${participant.name} (${participant.gamer_tag})
                </div>
            `;
        });
        detailsHTML += `</div>`;
    }
    
    // Oyun oylama durumu
    if (party.status === 'planning' || party.status === 'voting') {
        detailsHTML += `<h4>Oyun Seçenekleri:</h4><div class="game-grid">`;
        games.forEach(game => {
            detailsHTML += `
                <div class="game-card ${game.vote_count > 0 ? 'has-votes' : ''}">
                    <img src="images/games/${game.image}" alt="${game.title}" onerror="this.src='images/default-game.png'">
                    <h5>${game.title}</h5>
                    <div class="vote-count">${game.vote_count} oy</div>
                    ${currentUser && isParticipantInParty(party.party_id) ? 
                        `<button class="btn" onclick="voteForGame(${party.party_id}, ${game.game_id})">
                            <i class="fas fa-vote-yea"></i> Oy Ver
                        </button>` : ''}
                </div>
            `;
        });
        detailsHTML += `</div>`;
    }
    
    // Seçilen oyun
    if (party.selected_game_title) {
        detailsHTML += `
            <div class="alert alert-success">
                <strong>Seçilen Oyun:</strong> ${party.selected_game_title}
            </div>
        `;
    }
    
    // Slot bilgileri
    if (slots.length > 0) {
        detailsHTML += `
            <h4>Zaman Slotları:</h4>
            <div class="slot-voting-container">
        `;
        slots.forEach(slot => {
            const isFinalized = party.selected_slot_id == slot.slot_id;
            const userVote = slot.user_vote || null;
            
            detailsHTML += `
                <div class="slot-card ${isFinalized ? 'finalized-slot' : ''}" data-slot-id="${slot.slot_id}">
                    <div class="slot-header">
                        <div class="slot-time">${slot.start_time} - ${slot.end_time}</div>
                        <div class="slot-vote-count">${slot.yes_votes} ✓ / ${slot.no_votes} ✗</div>
                    </div>
                    <div class="slot-participants">
                        ${slot.participants ? slot.participants.map(p => `
                            <div class="participant-vote ${p.vote === 'yes' ? 'yes' : 'no'}">
                                <i class="fas fa-${p.vote === 'yes' ? 'check' : 'times'}"></i>
                                ${p.name}
                            </div>
                        `).join('') : ''}
                    </div>
                    <div class="slot-actions">
                        ${currentUser && !isFinalized ? `
                            <button class="vote-button yes ${userVote === 'yes' ? 'selected' : ''}" 
                                    onclick="voteForSlot(${slot.slot_id}, 'yes')">
                                <i class="fas fa-check"></i> ✓
                            </button>
                            <button class="vote-button no ${userVote === 'no' ? 'selected' : ''}" 
                                    onclick="voteForSlot(${slot.slot_id}, 'no')">
                                <i class="fas fa-times"></i> ✗
                            </button>
                        ` : ''}
                        ${isFinalized ? `
                            <span style="color: #28a745; font-weight: bold;">
                                <i class="fas fa-check-circle"></i> Seçilen Slot
                            </span>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        detailsHTML += `</div>`;
    }
    
    // Liderlik tablosu
    if (data.leaderboard && data.leaderboard.length > 0) {
        detailsHTML += `
            <h4>Liderlik Tablosu:</h4>
            <div class="leaderboard-card">
                ${data.leaderboard.map((player, index) => `
                    <div class="leaderboard-item">
                        <div class="leaderboard-rank">${index + 1}</div>
                        <div class="leaderboard-avatar">${player.name.charAt(0).toUpperCase()}</div>
                        <div class="leaderboard-info">
                            <div class="leaderboard-name">${player.name}</div>
                            <div class="leaderboard-tag">@${player.gamer_tag}</div>
                            <div class="leaderboard-stats">
                                <span>${player.wins || 0} G</span>
                                <span>${player.draws || 0} B</span>
                                <span>${player.losses || 0} M</span>
                            </div>
                        </div>
                        <div class="leaderboard-points">${player.total_points || 0}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Maç sonuçları ve skor girişi
    if (party.status === 'scheduled' || party.status === 'completed') {
        detailsHTML += `
            <h4>Maç Sonuçları:</h4>
            <div id="matchResults">
                ${data.matches && data.matches.length > 0 ? 
                    data.matches.map(match => `
                        <div class="match-card">
                            <h5>${match.game_title} - ${formatDate(match.match_date)}</h5>
                            <div class="alert alert-info">Maç tamamlandı</div>
                        </div>
                    `).join('') : 
                    `<div class="alert alert-info">Henüz maç oynanmadı.</div>`
                }
            </div>
        `;
        
        // Skor girişi (sadece host)
        if (currentUser && currentUser.user_id === party.host_id && party.status === 'scheduled') {
            detailsHTML += `
                <h4>Skor Girişi:</h4>
                <div class="score-entry">
                    <button class="btn btn-success" onclick="openScoreEntryModal(${party.party_id})">
                        <i class="fas fa-plus"></i> Maç Sonucu Gir
                    </button>
                </div>
            `;
        }
    }
    
    // Host işlemleri
    if (currentUser && currentUser.user_id === party.host_id) {
        detailsHTML += `
            <h4>Host İşlemleri:</h4>
            <div style="margin-top: 15px;">
                ${party.status === 'planning' ? 
                    `<button class="btn" onclick="startVoting(${party.party_id})">
                        <i class="fas fa-poll"></i> Oylamayı Başlat
                    </button>` : ''}
                ${party.status === 'voting' ? 
                    `<button class="btn" onclick="addTimeSlotToParty(${party.party_id})">
                        <i class="fas fa-clock"></i> Zaman Slotu Ekle
                    </button>
                    <button class="btn btn-success" onclick="finalizeParty(${party.party_id})">
                        <i class="fas fa-check"></i> Partiyi Sonlandır
                    </button>` : ''}
                ${party.status === 'scheduled' ? 
                    `<button class="btn btn-warning" onclick="completeParty(${party.party_id})">
                        <i class="fas fa-flag-checkered"></i> Partiyi Tamamla
                    </button>` : ''}
            </div>
        `;
    }
    
    currentParty = party;
    document.getElementById('partyDetailContent').innerHTML = detailsHTML;
}

function isParticipantInParty(partyId) {
    if (!currentUser) return false;
    
    // Check if current user is in the participants list
    const participants = currentParty ? currentParty.participants || [] : [];
    return participants.some(p => p.user_id === currentUser.user_id);
}

// Oyun oylama
function voteForGame(partyId, gameId) {
    if (!currentUser) {
        showAlert('Oy vermek için giriş yapın!', 'warning');
        return;
    }
    
    fetch(API_BASE + 'vote_game.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            party_id: partyId,
            game_id: gameId,
            user_id: currentUser.user_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Oyunuz kaydedildi!', 'success');
            viewPartyDetails(partyId); // Detayları yenile
        } else {
            showAlert(data.error || 'Oy verirken hata oluştu!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error voting:', error);
        showAlert('Oy verirken bağlantı hatası!', 'danger');
    });
}

// Slot oylama
function voteForSlot(slotId, choice) {
    if (!currentUser) {
        showAlert('Oy vermek için giriş yapın!', 'warning');
        return;
    }
    
    // Anında UI güncellemesi
    updateSlotVoteUI(slotId, choice);
    
    fetch(API_BASE + 'slots.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'vote',
            slot_id: slotId,
            user_id: currentUser.user_id,
            choice: choice
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sayıları güncelle
            updateSlotVoteCounts(slotId, data.yes_votes, data.no_votes);
        } else {
            showAlert(data.error || 'Slot oylama hatası!', 'danger');
            // Hata durumunda UI'yi geri al
            viewPartyDetails(currentParty.party_id);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bağlantı hatası!', 'danger');
        // Hata durumunda UI'yi geri al
        viewPartyDetails(currentParty.party_id);
    });
}

// Slot oy UI'sini anında güncelle
function updateSlotVoteUI(slotId, choice) {
    const slotCard = document.querySelector(`[data-slot-id="${slotId}"]`);
    
    if (!slotCard) {
        console.error('Slot card not found for slotId:', slotId);
        return;
    }
    
    // Buton durumlarını güncelle
    const yesButton = slotCard.querySelector('.vote-button.yes');
    const noButton = slotCard.querySelector('.vote-button.no');
    
    if (yesButton && noButton) {
        if (choice === 'yes') {
            yesButton.classList.add('selected');
            noButton.classList.remove('selected');
        } else {
            noButton.classList.add('selected');
            yesButton.classList.remove('selected');
        }
    }
}

// Slot oy sayılarını güncelle
function updateSlotVoteCounts(slotId, yesVotes, noVotes) {
    const slotCard = document.querySelector(`[data-slot-id="${slotId}"]`);
    if (!slotCard) return;
    
    const voteCount = slotCard.querySelector('.slot-vote-count');
    if (voteCount) {
        voteCount.textContent = `${yesVotes} ✓ / ${noVotes} ✗`;
    }
}

// Host işlemleri
function startVoting(partyId) {
    fetch(API_BASE + 'parties.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'update_status',
            party_id: partyId,
            status: 'voting',
            host_id: currentUser.user_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Oylama başlatıldı!', 'success');
            viewPartyDetails(partyId);
        } else {
            showAlert(data.error || 'Hata oluştu!', 'danger');
        }
    });
}

function addTimeSlotToParty(partyId) {
    const startTime = prompt('Başlangıç saati (HH:MM):');
    const endTime = prompt('Bitiş saati (HH:MM):');
    
    if (!startTime || !endTime) return;
    
    fetch(API_BASE + 'slots.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'create',
            party_id: partyId,
            start_time: startTime,
            end_time: endTime,
            slot_date: currentParty.party_date,
            host_id: currentUser.user_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Zaman slotu eklendi!', 'success');
            viewPartyDetails(partyId);
        } else {
            showAlert(data.error || 'Slot ekleme hatası!', 'danger');
        }
    });
}

function finalizeParty(partyId) {
    if (confirm('Partiyi sonlandırmak istediğinize emin misiniz?')) {
        fetch(API_BASE + 'parties.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_status',
                party_id: partyId,
                status: 'scheduled',
                host_id: currentUser.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Parti sonlandırıldı!', 'success');
                loadParties();
                closeModal('partyDetailModal');
            } else {
                showAlert(data.error || 'Hata oluştu!', 'danger');
            }
        });
    }
}

function finalizeSlot(partyId, slotId) {
    if (confirm('Bu slotu seçmek istediğinize emin misiniz? Diğer slotlar artık değiştirilemez.')) {
        fetch(API_BASE + 'slots.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'finalize',
                party_id: partyId,
                slot_id: slotId,
                host_id: currentUser.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Slot seçildi!', 'success');
                viewPartyDetails(partyId);
            } else {
                showAlert(data.error || 'Hata oluştu!', 'danger');
            }
        });
    }
}

// Skor girişi modalını aç
function openScoreEntryModal(partyId) {
    if (!currentUser) {
        showAlert('Skor girişi için giriş yapın!', 'warning');
        return;
    }
    
    // Oyunları yükle
    const gameSelect = document.getElementById('matchGame');
    gameSelect.innerHTML = '<option value="">Oyun seçin</option>';
    
    if (currentParty && currentParty.games) {
        currentParty.games.forEach(game => {
            const option = document.createElement('option');
            option.value = game.game_id;
            option.textContent = game.title;
            gameSelect.appendChild(option);
        });
    }
    
    // Katılımcıları yükle
    const playerScores = document.getElementById('playerScores');
    playerScores.innerHTML = '';
    
    if (currentParty && currentParty.participants) {
        currentParty.participants.forEach(participant => {
            const scoreRow = document.createElement('div');
            scoreRow.className = 'score-row';
            scoreRow.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label>${participant.name}</label>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control player-score" 
                               data-user-id="${participant.user_id}" 
                               placeholder="Skor" min="0">
                    </div>
                    <div class="col-md-5">
                        <select class="form-control player-result" data-user-id="${participant.user_id}">
                            <option value="win">Galibiyet</option>
                            <option value="draw">Beraberlik</option>
                            <option value="lose">Mağlubiyet</option>
                        </select>
                    </div>
                </div>
            `;
            playerScores.appendChild(scoreRow);
        });
    }
    
    // Bugünün tarihini ayarla
    const now = new Date();
    const dateInput = document.getElementById('matchDate');
    dateInput.value = now.toISOString().slice(0, 16);
    
    openModal('scoreEntryModal');
}

// Partiyi tamamla
function completeParty(partyId) {
    if (confirm('Partiyi tamamlamak istediğinize emin misiniz?')) {
        fetch(API_BASE + 'parties.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_status',
                party_id: partyId,
                status: 'completed',
                host_id: currentUser.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Parti tamamlandı!', 'success');
                viewPartyDetails(partyId);
            } else {
                showAlert(data.error || 'Hata oluştu!', 'danger');
            }
        });
    }
}

// Form Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Giriş formu
    document.getElementById('loginFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        const gamerTag = document.getElementById('loginGamerTag').value.trim();
        if (gamerTag) {
            loginUser(gamerTag);
        } else {
            showAlert('Lütfen gamer tag girin!', 'warning');
        }
    });
    
    // Kayıt formu
    document.getElementById('registerFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('registerName').value.trim();
        const gamerTag = document.getElementById('registerGamerTag').value.trim();
        
        if (!name || !gamerTag) {
            showAlert('Lütfen tüm alanları doldurun!', 'warning');
            return;
        }
        
        if (gamerTag.length < 3) {
            showAlert('Gamer tag en az 3 karakter olmalı!', 'warning');
            return;
        }
        
        registerUser(name, gamerTag);
    });
    
    // Skor girişi formu
    document.getElementById('scoreEntryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const gameId = document.getElementById('matchGame').value;
        const matchDate = document.getElementById('matchDate').value;
        
        if (!gameId || !matchDate) {
            showAlert('Lütfen oyun ve tarih seçin!', 'warning');
            return;
        }
        
        // Oyuncu skorlarını topla
        const scores = [];
        document.querySelectorAll('.player-score').forEach(input => {
            const userId = input.dataset.userId;
            const score = parseInt(input.value) || 0;
            const resultSelect = document.querySelector(`.player-result[data-user-id="${userId}"]`);
            const result = resultSelect.value;
            
            scores.push({
                user_id: parseInt(userId),
                score: score,
                result: result
            });
        });
        
        // Maç oluştur ve skorları kaydet
        const matchData = {
            party_id: currentParty.party_id,
            game_id: parseInt(gameId),
            match_date: matchDate,
            scores: scores
        };
        
        fetch(API_BASE + 'matches.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(matchData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Maç sonuçları kaydedildi!', 'success');
                closeModal('scoreEntryModal');
                viewPartyDetails(currentParty.party_id);
            } else {
                showAlert(data.error || 'Skor kaydedilirken hata oluştu!', 'danger');
            }
        })
        .catch(error => {
            console.error('Error saving scores:', error);
            showAlert('Skor kaydedilirken bağlantı hatası!', 'danger');
        });
    });
});

// Global functions for slot management
window.addTimeSlot = function() {
    const startTime = document.getElementById('slotStartTime').value;
    const endTime = document.getElementById('slotEndTime').value;
    
    if (!startTime || !endTime) {
        showAlert('Lütfen başlangıç ve bitiş saatini girin!', 'warning');
        return;
    }
    
    if (startTime >= endTime) {
        showAlert('Başlangıç saati bitiş saatinden önce olmalı!', 'warning');
        return;
    }
    
    // Slot'u listeye ekle
    const slotNumber = addedSlots.length + 1;
    addedSlots.push({
        id: slotNumber,
        start_time: startTime,
        end_time: endTime
    });
    
    // Listeyi güncelle
    updateSlotsList();
    
    // Input'ları temizle
    document.getElementById('slotStartTime').value = '';
    document.getElementById('slotEndTime').value = '';
    
    showAlert(`Slot ${slotNumber} eklendi!`, 'success');
};

window.removeTimeSlot = function(slotId) {
    addedSlots = addedSlots.filter(slot => slot.id !== slotId);
    updateSlotsList();
    showAlert('Slot silindi!', 'info');
};

console.log('Game+ Party Hub JavaScript loaded successfully!');
