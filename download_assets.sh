#!/bin/bash

AUDIO_DIR="Kobopoint Mobile/assets/audio"
mkdir -p "$AUDIO_DIR"

echo "Generating Audio Assets..."

# 1. Voice Files (Using Google TTS API via curl)
# We use 'tl=en-ng' if available, otherwise 'tl=en'
download_tts() {
    FILENAME=$1
    TEXT=$2
    # URL Encode the text (simple version)
    ENCODED_TEXT=$(echo "$TEXT" | sed 's/ /+/g')
    
    echo "Downloading $FILENAME..."
    curl -s -A "Mozilla/5.0" "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&q=$ENCODED_TEXT&tl=en" -o "$AUDIO_DIR/$FILENAME"
}

download_tts "voice_general.mp3" "Money don land!"
download_tts "voice_bank.mp3" "Bank alert don land!"
download_tts "voice_internal.mp3" "You don receive money from another user."
download_tts "voice_admin.mp3" "Admin don fund your wallet. Money don land!"

# 2. Alert Ding (Base64 Decode using Python3 standard lib)
# Short "Ding" sound (Glass Ping)
echo "Generating alert_ding.mp3..."
python3 -c "
import base64
import os

# Base64 of a short 'Glass Ping' sound (MP3)
b64_data = '/+MYxAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxGsAAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxJ8AAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxMoAAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/'
# The above was a placeholder in the previous attempt.
# Let's use a real, minimal valid MP3 frame header/silence if we can't get a real ding, 
# BUT actually, since I can curl, I'll just curl a ding sound too!
# https://notificationsounds.com is harder to curl.
# I will use the TTS 'Ding!' as a fallback if I can't find a direct link.
# Actually, TTS 'Ding!' is better than a broken base64.
"

# Alternative: TTS "Ding"
download_tts "alert_ding.mp3" "Ding!"

echo "Audio assets generated in $AUDIO_DIR"
ls -lh "$AUDIO_DIR"
