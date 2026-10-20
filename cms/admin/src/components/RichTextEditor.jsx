import { useEffect, useRef, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Underline from '@tiptap/extension-underline';
import { api } from '../lib/api';

function ToolbarButton({ active, onClick, label, children, disabled }) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      aria-pressed={active}
      disabled={disabled}
      onMouseDown={(e) => e.preventDefault()}
      onClick={onClick}
      className={`h-8 min-w-8 rounded-lg px-2 text-sm font-semibold transition disabled:opacity-40 ${active ? 'bg-navy text-gold' : 'text-navy hover:bg-cream-2'}`}
    >
      {children}
    </button>
  );
}

/**
 * TipTap editor that reads/writes HTML. The toolbar only offers what the
 * backend sanitizer keeps (headings h2-h4, lists, links, images ...), so what
 * the editor shows is what gets published.
 */
export default function RichTextEditor({ value, onChange, placeholder = 'Write the article…' }) {
  const fileInput = useRef(null);
  const [uploading, setUploading] = useState(false);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3, 4] }, codeBlock: false }),
      Underline,
      Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { rel: 'noopener noreferrer' } }),
      Image,
    ],
    content: value || '',
    editorProps: {
      attributes: { class: 'prose-editor px-4 py-3', 'aria-label': 'Article body', 'data-placeholder': placeholder },
    },
    onUpdate: ({ editor: e }) => onChange(e.isEmpty ? '' : e.getHTML()),
  });

  // Load new content when a different article is opened.
  useEffect(() => {
    if (editor && value !== editor.getHTML() && !(value === '' && editor.isEmpty)) {
      editor.commands.setContent(value || '', false);
    }
  }, [value, editor]);

  if (!editor) return null;

  const setLink = () => {
    const prev = editor.getAttributes('link').href || '';
    const url = window.prompt('Link URL (leave empty to remove)', prev);
    if (url === null) return;
    if (url === '') editor.chain().focus().unsetLink().run();
    else editor.chain().focus().extendMarkRange('link').setLink({ href: url, target: /^https?:/i.test(url) ? '_blank' : null }).run();
  };

  const insertImage = async (file) => {
    if (!file) return;
    setUploading(true);
    try {
      const { url } = await api.upload(file);
      editor.chain().focus().setImage({ src: url, alt: file.name.replace(/\.[^.]+$/, '') }).run();
    } catch (e) {
      window.alert(e.message);
    } finally {
      setUploading(false);
      fileInput.current.value = '';
    }
  };

  const chain = () => editor.chain().focus();

  return (
    <div className="rounded-xl border border-slate-200 bg-white focus-within:border-gold focus-within:ring-4 focus-within:ring-gold/15">
      <div className="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 rounded-t-xl border-b border-slate-100 bg-white/95 p-1.5 backdrop-blur">
        <ToolbarButton label="Bold" active={editor.isActive('bold')} onClick={() => chain().toggleBold().run()}><b>B</b></ToolbarButton>
        <ToolbarButton label="Italic" active={editor.isActive('italic')} onClick={() => chain().toggleItalic().run()}><i>I</i></ToolbarButton>
        <ToolbarButton label="Underline" active={editor.isActive('underline')} onClick={() => chain().toggleUnderline().run()}><u>U</u></ToolbarButton>
        <ToolbarButton label="Strikethrough" active={editor.isActive('strike')} onClick={() => chain().toggleStrike().run()}><s>S</s></ToolbarButton>
        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton label="Heading" active={editor.isActive('heading', { level: 2 })} onClick={() => chain().toggleHeading({ level: 2 }).run()}>H2</ToolbarButton>
        <ToolbarButton label="Subheading" active={editor.isActive('heading', { level: 3 })} onClick={() => chain().toggleHeading({ level: 3 }).run()}>H3</ToolbarButton>
        <ToolbarButton label="Bullet list" active={editor.isActive('bulletList')} onClick={() => chain().toggleBulletList().run()}>• List</ToolbarButton>
        <ToolbarButton label="Numbered list" active={editor.isActive('orderedList')} onClick={() => chain().toggleOrderedList().run()}>1. List</ToolbarButton>
        <ToolbarButton label="Quote" active={editor.isActive('blockquote')} onClick={() => chain().toggleBlockquote().run()}>❝</ToolbarButton>
        <ToolbarButton label="Divider" onClick={() => chain().setHorizontalRule().run()}>―</ToolbarButton>
        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton label="Link" active={editor.isActive('link')} onClick={setLink}>🔗</ToolbarButton>
        <ToolbarButton label="Insert image" disabled={uploading} onClick={() => fileInput.current?.click()}>{uploading ? '…' : '🖼'}</ToolbarButton>
        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton label="Undo" disabled={!editor.can().undo()} onClick={() => chain().undo().run()}>↶</ToolbarButton>
        <ToolbarButton label="Redo" disabled={!editor.can().redo()} onClick={() => chain().redo().run()}>↷</ToolbarButton>
      </div>
      <EditorContent editor={editor} />
      <input ref={fileInput} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={(e) => insertImage(e.target.files?.[0])} />
    </div>
  );
}
