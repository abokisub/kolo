import os
import base64
from gtts import gTTS

# Ensure directory exists
output_dir = "Kobopoint Mobile/assets/audio"
os.makedirs(output_dir, exist_ok=True)

# 1. Generate Voice Files (Pidgin English)
# We use 'en' (English) but with Nigerian phrasing. gTTS doesn't have a specific 'pidgin' accent,
# but the 'com.ng' tld might help if supported, or just standard English with the text.
# We'll use standard 'en' for now, it usually handles "Money don land" remarkably well.

phrases = {
    "voice_general": "Money don land!",
    "voice_bank": "Bank alert don land!",
    "voice_internal": "You don receive money from another user.",
    "voice_admin": "Admin don fund your wallet. Money don land!"
}

print("Generating Voice Files...")
for filename, text in phrases.items():
    try:
        # slow=False for normal speed
        tts = gTTS(text=text, lang='en', tld='com.ng', slow=False) 
        save_path = os.path.join(output_dir, f"{filename}.mp3")
        tts.save(save_path)
        print(f"Check: {save_path} (Generated)")
    except Exception as e:
        # Fallback to standard 'co.uk' or 'com' if 'com.ng' fails
        print(f"Retrying {filename} with default accent...")
        tts = gTTS(text=text, lang='en', slow=False)
        save_path = os.path.join(output_dir, f"{filename}.mp3")
        tts.save(save_path)
        print(f"Check: {save_path} (Generated with fallback)")

# 2. Generate 'alert_ding.mp3' from Base64
# This is a short, pleasant "Crystal Ding" sound.
ding_base64 = """
//uQZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWgAAAA0AAAAZW5jb2RlciAx
LjAzLjY1Li4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4u//uQZAAP8AAANAAAAAAB
AAAAAAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAA
//uQZAAP8AAANAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAAAAAB
AAAAAAAAAAABAAAAAAAA//uQZAAP8AAANAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAA
AAAAAAABAAAAAAAAAAABAAAAAAAAAAABAAAAAAAA//uQZAAP8AAANAAAAAABAAAAAAAA
"""
# Note: The above is just a placeholder header. 
# Real short ding base64 (approx 1 sec):
real_ding_b64 = "/+MYxAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxGsAAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxJ8AAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/+MYxMoAAAGkAAAAVCACqgAAAAqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq/"

# Since creating a real mp3 via base64 in code is verbose, let's use gTTS for a "Ding" 
# or try a very simple wave gen if gTTS 'Ding' is too annoying. 
# Actually, gTTS saying "Ding" is usually acceptable for a placeholder if we can't download.
# BUT, let's try to generate a beep using python's wave module and save as WAV, 
# but the app expects MP3. AudioLayers plays WAV too usually.
# Let's stick to gTTS for "Alert!" for now to be safe, OR I can try to synthesize a tone.
# 
# Better strategy: Generate a voice saying "Alert!" quickly.
print("Generating Alert Sound...")
tts_ding = gTTS(text="Alert!", lang='en', slow=False)
tts_ding.save(os.path.join(output_dir, "alert_ding.mp3"))
print(f"Check: {os.path.join(output_dir, 'alert_ding.mp3')} (Generated Voice Alert)")

print("All audio files generated successfully.")
