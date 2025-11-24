
import re

def check_nesting(filename):
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove blade comments
    content = re.sub(r'{{--.*?--}}', '', content, flags=re.DOTALL)
    
    # Tokenize
    tokens = re.finditer(r'(<div\b|</div>)', content)
    
    level = 0
    min_level = 0
    
    for match in tokens:
        token = match.group(1)
        if token.startswith('<div'):
            level += 1
        else:
            level -= 1
            if level < min_level:
                min_level = level
                print(f"Premature closing at position {match.start()}")
                # Get line number
                line_num = content[:match.start()].count('\n') + 1
                print(f"Line: {line_num}")

    print(f"Final level: {level}")
    print(f"Min level: {min_level}")

check_nesting('resources/views/livewire/products/management/tabs/basic-tab.blade.php')
