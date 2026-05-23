// When brand changes, load dealerships
document.getElementById('brandSelect').addEventListener('change', function() {
    const brandId = this.value;
    const group = document.getElementById('dealershipGroup');
    const list  = document.getElementById('dealershipList');

    if (!brandId) { group.style.display = 'none'; return; }

    fetch(`api/dealerships.php?brand_id=${brandId}`)
        .then(r => r.json())
        .then(dealerships => {
            list.innerHTML = '';
            dealerships.forEach(d => {
                const item = document.createElement('label');
                item.className = 'dealership-item';
                item.innerHTML = `
                    <input type="checkbox" name="dealership" value="${d.id}" data-name="${d.name}">
                    ${d.name}
                `;
                list.appendChild(item);
            });
            group.style.display = 'block';
        });
});

// Logo toggle
document.getElementById('logoToggle').addEventListener('change', function() {
    document.getElementById('logoOptions').style.display = this.checked ? 'flex' : 'none';
    document.getElementById('logoToggleLabel').textContent = this.checked ? 'Yes' : 'No';
});

// Image preview
document.getElementById('bgImage').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('previewImg');
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// Select all / deselect all
function selectAll() {
    document.querySelectorAll('input[name="dealership"]').forEach(cb => cb.checked = true);
}
function deselectAll() {
    document.querySelectorAll('input[name="dealership"]').forEach(cb => cb.checked = false);
}

// Generate creatives
function generateCreatives() {
    const bgFile = document.getElementById('bgImage').files[0];
    if (!bgFile) { alert('Please upload a background image!'); return; }

    const dealerships = [...document.querySelectorAll('input[name="dealership"]:checked')]
        .map(cb => cb.value);
    if (dealerships.length === 0) { alert('Please select at least one dealership!'); return; }

    const formats = [...document.querySelectorAll('input[name="format"]:checked')]
        .map(cb => cb.value);
    if (formats.length === 0) { alert('Please select at least one output format!'); return; }

    const logoEnabled = document.getElementById('logoToggle').checked;
    const logoType    = document.querySelector('input[name="logoType"]:checked')?.value || 'logo_dark';

    const formData = new FormData();
    formData.append('bg_image', bgFile);
    formData.append('dealerships', JSON.stringify(dealerships));
    formData.append('formats', JSON.stringify(formats));
    formData.append('logo_enabled', logoEnabled ? '1' : '0');
    formData.append('logo_type', logoType);

    // Show progress
    document.getElementById('progressArea').style.display = 'block';
    document.getElementById('downloadArea').style.display = 'none';
    document.getElementById('generateBtn').disabled = true;
    setProgress(10, 'Uploading image...');

    fetch('generate.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            setProgress(100, 'Done!');
            if (data.error) { alert('Error: ' + data.error); return; }
    
            const dl = document.getElementById('downloadLink');
            dl.href = data.zip_url;
    
            // Individual downloads
            const individualDiv = document.getElementById('individualDownloads');
            individualDiv.innerHTML = '<p style="font-weight:600;margin-bottom:8px">Or download individually:</p>';
            data.files.forEach(file => {
                const a = document.createElement('a');
                a.href = file.url;
                a.textContent = '⬇ ' + file.name;
                a.className = 'individual-btn';
                a.download = file.name;
                individualDiv.appendChild(a);
            });

            document.getElementById('downloadArea').style.display = 'block';
            document.getElementById('generateBtn').disabled = false;
        })
        .catch(err => {
            alert('Something went wrong!');
            document.getElementById('generateBtn').disabled = false;
        });
}

function setProgress(pct, text) {
    document.getElementById('progressFill').style.width = pct + '%';
    document.getElementById('progressText').textContent = text;
}