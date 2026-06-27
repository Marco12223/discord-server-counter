document.addEventListener('DOMContentLoaded', () => {
    const tokenInput = document.querySelector('input[name="token"]');
    if (tokenInput) {
        tokenInput.focus();
    }

    // sort state: { type, direction }
    // direction: 'default' | 'asc' | 'desc'
    let sortState = { type: null, direction: 'default' };
    let currentGuilds = [];

    // If logged in, fetch guilds async and render
    const guildsList = document.getElementById('guilds-list');
    const guildsSpinner = document.getElementById('guilds-spinner');
    const guildCountEl = document.getElementById('guild-count');

    function applySort(guilds) {
        if (!sortState.type || sortState.direction === 'default') return guilds;

        const sorted = [...guilds];
        sorted.sort((a, b) => {
            let valA, valB;
            if (sortState.type === 'name') {
                valA = (a.name || '').toLowerCase();
                valB = (b.name || '').toLowerCase();
                const cmp = valA.localeCompare(valB);
                return sortState.direction === 'asc' ? cmp : -cmp;
            } else if (sortState.type === 'members') {
                valA = a.member_count ?? -1;
                valB = b.member_count ?? -1;
                const cmp = valA - valB;
                return sortState.direction === 'asc' ? cmp : -cmp;
            } else if (sortState.type === 'joined') {
                valA = a.joined_at ? new Date(a.joined_at).getTime() : 0;
                valB = b.joined_at ? new Date(b.joined_at).getTime() : 0;
                const cmp = valA - valB;
                return sortState.direction === 'asc' ? cmp : -cmp;
            }
            return 0;
        });
        return sorted;
    }

    function renderGuilds(guilds) {
        if (!guildsList) return;
        guildsList.innerHTML = '';

        const sorted = applySort(guilds);
        for (const g of sorted) {
            const item = document.createElement('div');
            item.className = 'item';

            const img = document.createElement('img');
            if (g.icon) {
                img.src = `https://cdn.discordapp.com/icons/${g.id}/${g.icon}.png`;
            } else {
                img.src = 'https://cdn.discordapp.com/embed/avatars/0.png';
            }
            img.alt = g.name || 'Server';

            const meta = document.createElement('div');
            meta.className = 'meta';

            const title = document.createElement('span');
            title.textContent = g.name || 'Unbekannt';

            const code = document.createElement('code');
            code.textContent = g.id || '';

            meta.appendChild(title);
            meta.appendChild(code);

            // create single-line info row with icons for joined date and members
            const infoRow = document.createElement('div');
            infoRow.className = 'info-row';

            if (g.joined_at) {
                const d = new Date(g.joined_at);
                if (!isNaN(d)) {
                    const day = String(d.getDate()).padStart(2, '0');
                    const mon = String(d.getMonth() + 1).padStart(2, '0');
                    const year = d.getFullYear();

                    const joinedItem = document.createElement('div');
                    joinedItem.className = 'info-item';
                    joinedItem.innerHTML = `<i class="fas fa-calendar-days"></i> ${day}.${mon}.${year}`;
                    infoRow.appendChild(joinedItem);
                }
            }

            if (g.member_count != null) {
                const membersItem = document.createElement('div');
                membersItem.className = 'info-item';
                membersItem.innerHTML = `<i class="fas fa-user"></i> ${g.member_count}`;
                infoRow.appendChild(membersItem);
            }

            if (infoRow.children.length) {
                meta.appendChild(infoRow);
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'copy-btn';
            btn.dataset.copyValue = g.id || '';
            btn.setAttribute('aria-label', 'Server-ID kopieren');
            btn.innerHTML = '<i class="fas fa-copy"></i>';

            item.appendChild(img);
            item.appendChild(meta);
            item.appendChild(btn);

            guildsList.appendChild(item);
        }
    }

    async function loadGuilds() {
        if (!guildsList || !guildsSpinner) return;
        guildsList.innerHTML = '';
        guildsSpinner.style.display = 'block';

        try {
            const res = await fetch('api_guilds.php', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Netzwerkfehler');
            const data = await res.json();
            currentGuilds = data.guilds || [];

            // update count
            if (guildCountEl) guildCountEl.textContent = `${currentGuilds.length} Server`;

            // render with current sort state
            renderGuilds(currentGuilds);
        } catch (err) {
            console.error('Fehler beim Laden der Server:', err);
            guildsList.innerHTML = '<div class="error">Fehler beim Laden der Server.</div>';
        } finally {
            guildsSpinner.style.display = 'none';
        }
    }

    // delegated event handler for copy and sort buttons
    document.body.addEventListener('click', async (e) => {
        const copyBtn = e.target.closest('.copy-btn');
        if (copyBtn) {
            const value = copyBtn.dataset.copyValue || '';
            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
                const original = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => copyBtn.innerHTML = original, 1200);
            } catch (err) {
                console.error('Clipboard-Fehler:', err);
            }
            return;
        }

        // sort button handler
        const sortBtn = e.target.closest('.sort-btn');
        if (sortBtn) {
            const sortType = sortBtn.dataset.sortType;
            const allSortBtns = document.querySelectorAll('.sort-btn');

            // toggle direction for same button, reset for different button
            if (sortState.type === sortType) {
                sortState.direction = sortState.direction === 'default' ? 'asc' : sortState.direction === 'asc' ? 'desc' : 'default';
            } else {
                sortState.type = sortType;
                sortState.direction = 'asc';
            }

            // update button UI
            allSortBtns.forEach(b => {
                b.classList.remove('active');
                b.innerHTML = b.innerHTML.replace(/<i class="fa-solid fa-[^"]+"><\/i>/g, '<i class="fa-solid fa-up-down"></i>');
            });

            if (sortState.direction !== 'default') {
                sortBtn.classList.add('active');
                const icon = sortState.direction === 'asc' ? 'fa-up-long' : 'fa-down-long';
                const label = sortBtn.dataset.sortType === 'name' ? 'Servername' :
                    sortBtn.dataset.sortType === 'members' ? 'Mitglieder' : 'Hinzugefügt';
                sortBtn.innerHTML = `<i class="fa-solid ${icon}"></i> ${label}`;
            }

            // apply sorting
            renderGuilds(currentGuilds);
        }
    });

    // Start loading if session token exists (server set it)
    if (window.__loggedIn) {
        guildsSpinner.style.display = 'block';
        loadGuilds();
    }
});
