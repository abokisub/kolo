
import sys

def check_braces(filename):
    with open(filename, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    depth = 0
    for i, line in enumerate(lines):
        for char in line:
            if char == '{':
                depth += 1
            elif char == '}':
                depth -= 1
        
        if depth < 0:
            print(f"Error: Negative depth at line {i+1}: {line.strip()}")
            return
    
    print(f"Final depth: {depth}")

if __name__ == "__main__":
    check_braces(sys.argv[1])
