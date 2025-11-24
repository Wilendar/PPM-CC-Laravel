
import re
import sys

def count_divs(filename):
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Remove blade comments
        content = re.sub(r'{{--.*?--}}', '', content, flags=re.DOTALL)
        
        open_divs = len(re.findall(r'<div\b', content))
        close_divs = len(re.findall(r'</div>', content))
        
        print(f"File: {filename}")
        print(f"Open divs: {open_divs}")
        print(f"Close divs: {close_divs}")
        
        if open_divs != close_divs:
            print("MISMATCH!")
        else:
            print("Balanced.")
    except Exception as e:
        print(f"Error reading {filename}: {e}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        for filename in sys.argv[1:]:
            count_divs(filename)
    else:
        print("Usage: python count_divs.py <filename>")
