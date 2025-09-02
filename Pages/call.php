<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$other_user_id = (int)$_GET['user'];

// Fetch other user's details
$stmt = $conn->prepare("SELECT full_name, avatar_url FROM users WHERE id = ?");
$stmt->bind_param('i', $other_user_id);
$stmt->execute();
$other_user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$page_title = "Audio Call with " . ($other_user['full_name'] ?? "User $other_user_id");
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .call-container {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
            position: relative;
            border-radius: 2rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 64rem;
            min-height: 40rem;
            padding: 3rem;
        }
        .call-container:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        .control-btn {
            transition: all 0.3s ease;
            transform: scale(1);
        }
        .control-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        .control-btn.active {
            background: #059669;
        }
        .control-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .wave-animation {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            height: 120px;
            transform: scale(1.8);
        }
        .wave-bar {
            width: 10px;
            height: 20px;
            background: #10b981;
            border-radius: 5px;
            transition: height 0.05s ease, background 0.05s ease;
        }
        .end-call-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #000000;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 2rem;
            z-index: 10;
        }
        .end-call-overlay.active {
            display: flex;
        }
        .cross-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .cross-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        .avatar-container {
            transition: transform 0.3s ease;
        }
        .avatar-container:hover {
            transform: scale(1.05);
        }
        .controls-container {
            position: absolute;
            bottom: 3rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 2rem;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center">
    <div class="container mx-auto max-w-5xl p-10">
        <div class="call-container bg-white flex flex-col items-center justify-between">
            <div class="cross-btn" id="crossBtn">
                <i class="fas fa-times text-white text-2xl"></i>
            </div>
            <div class="end-call-overlay" id="endCallOverlay">
                <div class="cross-btn" id="overlayCrossBtn">
                    <i class="fas fa-times text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-6">Call Ended</h2>
                <p class="text-xl text-white mb-6" id="callDuration"></p>
                <button id="closeOverlay" class="control-btn px-8 py-3 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 text-lg">
                    Back to Messages
                </button>
            </div>
            <div class="flex flex-col items-center space-y-10 mt-16">
                <div class="avatar-container flex items-center space-x-8">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-300 flex items-center justify-center shadow-lg overflow-hidden">
                            <?php if ($other_user['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($other_user['avatar_url']); ?>" class="w-full h-full object-cover" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user text-emerald-600 text-4xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-400 border-2 border-white rounded-full"></div>
                    </div>
                    <div>
                        <h2 class="text-4xl font-bold text-gray-800"><?php echo htmlspecialchars($other_user['full_name'] ?? "User $other_user_id"); ?></h2>
                        <p class="text-xl text-gray-600" id="callStatus">Connecting...</p>
                    </div>
                </div>
                <div class="flex items-center justify-center">
                    <div class="wave-animation" id="waveAnimation">
                        <span class="wave-bar" id="waveBar1"></span>
                        <span class="wave-bar" id="waveBar2"></span>
                        <span class="wave-bar" id="waveBar3"></span>
                        <span class="wave-bar" id="waveBar4"></span>
                        <span class="wave-bar" id="waveBar5"></span>
                        <span class="wave-bar" id="waveBar6"></span>
                        <span class="wave-bar" id="waveBar7"></span>
                    </div>
                </div>
            </div>
            <audio id="remoteAudio" autoplay></audio>
            <div class="controls-container">
                <button id="muteBtn" class="control-btn w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-100">
                    <i class="fas fa-microphone text-emerald-600 text-3xl"></i>
                </button>
                <button id="endCall" class="control-btn px-10 py-4 bg-red-600 text-white rounded-full hover:bg-red-700 text-xl">
                    <i class="fas fa-phone text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let localStream, peerConnection, audioContext, analyser, source;
        const remoteAudio = document.getElementById('remoteAudio');
        const muteBtn = document.getElementById('muteBtn');
        const endCallBtn = document.getElementById('endCall');
        const crossBtn = document.getElementById('crossBtn');
        const callStatus = document.getElementById('callStatus');
        const endCallOverlay = document.getElementById('endCallOverlay');
        const closeOverlayBtn = document.getElementById('closeOverlay');
        const overlayCrossBtn = document.getElementById('overlayCrossBtn');
        const waveAnimation = document.getElementById('waveAnimation');
        const waveBars = [
            document.getElementById('waveBar1'),
            document.getElementById('waveBar2'),
            document.getElementById('waveBar3'),
            document.getElementById('waveBar4'),
            document.getElementById('waveBar5'),
            document.getElementById('waveBar6'),
            document.getElementById('waveBar7')
        ];
        const userId = <?php echo $user_id; ?>;
        const otherUserId = <?php echo $other_user_id; ?>;
        const ws = new WebSocket(`ws://localhost:8080?userId=${userId}`);
        let startTime;

        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ],
            codecs: [
                {
                    mimeType: 'audio/opus',
                    clockRate: 48000,
                    channels: 2
                }
            ]
        };

        function formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function setupAudioAnalyser() {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 128;
            analyser.smoothingTimeConstant = 0.8;
            source = audioContext.createMediaStreamSource(localStream);
            source.connect(analyser);

            const dataArray = new Uint8Array(analyser.frequencyBinCount);
            function updateWave() {
                if (!analyser) return;
                analyser.getByteFrequencyData(dataArray);
                const avg = dataArray.reduce((sum, val) => sum + val, 0) / dataArray.length;
                const normalized = Math.min(avg / 80, 1.5);
                waveBars.forEach((bar, index) => {
                    const height = 20 + normalized * 100;
                    bar.style.height = `${height}px`;
                    const intensity = Math.min(normalized * 255, 255);
                    bar.style.background = `rgb(${intensity}, 185, 129)`;
                });
                requestAnimationFrame(updateWave);
            }
            updateWave();
        }

        async function startCall() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        sampleRate: 48000,
                        channelCount: 2,
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    },
                    video: false
                });
                startTime = Date.now();
                callStatus.textContent = 'Connecting...';
                setupAudioAnalyser();

                peerConnection = new RTCPeerConnection(configuration);
                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

                peerConnection.ontrack = (event) => {
                    remoteAudio.srcObject = event.streams[0];
                    callStatus.textContent = 'Connected';
                    waveAnimation.style.display = 'flex';
                    // Send start time to the other user
                    ws.send(JSON.stringify({
                        type: 'start-time',
                        startTime: startTime,
                        to: otherUserId,
                        from: userId
                    }));
                };

                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        ws.send(JSON.stringify({
                            type: 'ice-candidate',
                            candidate: event.candidate,
                            to: otherUserId,
                            from: userId
                        }));
                    }
                };

                setInterval(async () => {
                    if (peerConnection && peerConnection.getStats) {
                        const stats = await peerConnection.getStats();
                        stats.forEach(report => {
                            if (report.type === 'inbound-rtp' && report.kind === 'audio') {
                                console.log('Audio Stats:', {
                                    packetsLost: report.packetsLost,
                                    jitter: report.jitter,
                                    packetsReceived: report.packetsReceived
                                });
                                if (report.packetsLost > 0 || report.jitter > 0.1) {
                                    callStatus.textContent = 'Poor network quality';
                                }
                            }
                        });
                    }
                }, 5000);

                const offer = await peerConnection.createOffer({
                    offerToReceiveAudio: true,
                    offerToReceiveVideo: false
                });
                await peerConnection.setLocalDescription(offer);
                ws.send(JSON.stringify({
                    type: 'offer',
                    offer: offer,
                    to: otherUserId,
                    from: userId
                }));
            } catch (error) {
                console.error('Error starting call:', error);
                callStatus.textContent = 'Failed to connect';
                waveAnimation.style.display = 'none';
                alert('Failed to access microphone. Please ensure permissions are granted and check your audio device.');
            }
        }

        function endCall(duration = null) {
            if (peerConnection) peerConnection.close();
            if (localStream) localStream.getTracks().forEach(track => track.stop());
            if (audioContext) audioContext.close();
            if (ws && ws.readyState === WebSocket.OPEN) {
                const calculatedDuration = duration || Math.floor((Date.now() - startTime) / 1000);
                ws.send(JSON.stringify({
                    type: 'end-call',
                    to: otherUserId,
                    from: userId,
                    duration: calculatedDuration
                }));
                ws.close();
            }
            callStatus.textContent = 'Disconnected';
            waveAnimation.style.display = 'none';
            const finalDuration = duration || Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('callDuration').textContent = `Call duration: ${formatDuration(finalDuration)}`;
            endCallOverlay.classList.add('active');
            endCallBtn.classList.add('disabled');
            crossBtn.classList.add('disabled');
        }

        ws.onopen = () => {
            console.log('WebSocket connected');
            startCall();
        };

        ws.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            if (message.to != userId) return;

            try {
                if (message.type === 'start-time') {
                    startTime = message.startTime; // Synchronize start time
                } else if (message.type === 'offer') {
                    if (!peerConnection) {
                        peerConnection = new RTCPeerConnection(configuration);
                        peerConnection.ontrack = (event) => {
                            remoteAudio.srcObject = event.streams[0];
                            callStatus.textContent = 'Connected';
                            waveAnimation.style.display = 'flex';
                        };
                        peerConnection.onicecandidate = (event) => {
                            if (event.candidate) {
                                ws.send(JSON.stringify({
                                    type: 'ice-candidate',
                                    candidate: event.candidate,
                                    to: otherUserId,
                                    from: userId
                                }));
                            }
                        };
                        localStream = await navigator.mediaDevices.getUserMedia({
                            audio: {
                                sampleRate: 48000,
                                channelCount: 2,
                                echoCancellation: true,
                                noiseSuppression: true,
                                autoGainControl: true
                            },
                            video: false
                        });
                        startTime = Date.now(); // Set temporarily, will be overridden by start-time message
                        setupAudioAnalyser();
                        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
                    }
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    ws.send(JSON.stringify({
                        type: 'answer',
                        answer: answer,
                        to: message.from,
                        from: userId
                    }));
                } else if (message.type === 'answer') {
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
                } else if (message.type === 'ice-candidate') {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                } else if (message.type === 'end-call') {
                    callStatus.textContent = 'Disconnected';
                    waveAnimation.style.display = 'none';
                    if (peerConnection) peerConnection.close();
                    if (localStream) localStream.getTracks().forEach(track => track.stop());
                    if (audioContext) audioContext.close();
                    if (ws) ws.close();
                    const duration = message.duration || Math.floor((Date.now() - startTime) / 1000);
                    document.getElementById('callDuration').textContent = `Call duration: ${formatDuration(duration)}`;
                    endCallOverlay.classList.add('active');
                    endCallBtn.classList.add('disabled');
                    crossBtn.classList.add('disabled');
                }
            } catch (error) {
                console.error('Error handling WebSocket message:', error);
            }
        };

        ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            callStatus.textContent = 'Connection error';
            waveAnimation.style.display = 'none';
            alert('Network error occurred. Please check your connection.');
        };

        ws.onclose = () => {
            callStatus.textContent = 'Disconnected';
            waveAnimation.style.display = 'none';
            endCall();
        };

        let isMuted = false;
        muteBtn.addEventListener('click', () => {
            isMuted = !isMuted;
            localStream.getAudioTracks().forEach(track => (track.enabled = !isMuted));
            muteBtn.innerHTML = `<button id="muteBtn" class="control-btn w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-100"><i class="fas ${isMuted ? 'fa-microphone-slash' : 'fa-microphone'} text-emerald-600 text-3xl"></i></button>`;
            muteBtn.classList.toggle('active', isMuted);
        });

        endCallBtn.addEventListener('click', () => endCall());
        crossBtn.addEventListener('click', () => endCall());
        closeOverlayBtn.addEventListener('click', () => {
            window.location.href = '/TradeHub/Pages/messages.php?user=<?php echo $other_user_id; ?>';
        });
        overlayCrossBtn.addEventListener('click', () => {
            window.location.href = '/TradeHub/Pages/messages.php?user=<?php echo $other_user_id; ?>';
        });

        window.addEventListener('beforeunload', () => {
            if (peerConnection) peerConnection.close();
            if (localStream) localStream.getTracks().forEach(track => track.stop());
            if (audioContext) audioContext.close();
            if (ws && ws.readyState === WebSocket.OPEN) {
                const duration = Math.floor((Date.now() - startTime) / 1000);
                ws.send(JSON.stringify({
                    type: 'end-call',
                    to: otherUserId,
                    from: userId,
                    duration: duration
                }));
                ws.close();
            }
        });
    </script>
</body>
</html>
<?php include '../includes/footer.php'; ?>