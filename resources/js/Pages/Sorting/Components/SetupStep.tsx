import { useState, FormEvent } from 'react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

interface Folder {
    id: string;
    name: string;
    parents?: string[];
}

interface SortingSession {
    id: number;
    source_folder_id: string;
    source_folder_name: string;
    destination_folders: Folder[];
    images: any[];
    total_images: number;
    sorted_images: number;
    remaining_images: number;
    current_image_index: number;
    status: 'setup' | 'active' | 'completed' | 'paused';
}

interface Props {
    session: SortingSession;
    onSessionUpdate: (session: SortingSession) => void;
    onStartSorting: () => void;
    onReset: () => void;
}

export default function SetupStep({ session, onSessionUpdate, onStartSorting, onReset }: Props) {
    const [folderName, setFolderName] = useState('');
    const [loading, setLoading] = useState(false);

    const handleAddFolder = async (e: FormEvent) => {
        e.preventDefault();

        if (!folderName.trim()) {
            return;
        }

        if ((session.destination_folders?.length ?? 0) >= 9) {
            alert('Maximum 9 folders allowed for keyboard shortcuts');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post('/sorting/session/folder', {
                session_id: session.id,
                folder_name: folderName.trim(),
            });

            onSessionUpdate(response.data.session);
            setFolderName('');
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to create folder');
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveFolder = async (folderId: string) => {
        setLoading(true);
        try {
            const response = await axios.delete('/sorting/session/folder', {
                data: {
                    session_id: session.id,
                    folder_id: folderId,
                },
            });

            onSessionUpdate(response.data.session);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to remove folder');
        } finally {
            setLoading(false);
        }
    };

    const handleStartSorting = async () => {
        if ((session.destination_folders?.length ?? 0) === 0) {
            alert('Please create at least one destination folder');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post('/sorting/session/start', {
                session_id: session.id,
            });

            onSessionUpdate(response.data.session);
            onStartSorting();
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to start sorting');
        } finally {
            setLoading(false);
        }
    };

    const handleReset = async () => {
        if (!confirm('Are you sure you want to start over? This will delete the current session.')) {
            return;
        }

        setLoading(true);
        try {
            await axios.delete('/sorting/session', {
                data: { session_id: session.id },
            });

            onReset();
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to reset session');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
            {/* Header */}
            <div className="bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-6 text-white">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-3 mb-2">
                            <div className="w-8 h-8 bg-white bg-opacity-30 rounded-full flex items-center justify-center text-sm font-bold">
                                2
                            </div>
                            <h2 className="text-2xl font-bold">Setup Destination Folders</h2>
                        </div>
                        <p className="text-blue-100 text-sm">
                            Source: <span className="font-medium">{session.source_folder_name}</span>
                        </p>
                        <p className="text-blue-100 text-sm">
                            {session.total_images} image{session.total_images !== 1 ? 's' : ''} found
                        </p>
                    </div>
                    <button
                        onClick={handleReset}
                        className="text-white hover:text-blue-100 transition-colors"
                        title="Start over"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div className="px-8 py-10">
                <div className="max-w-3xl mx-auto">
                    {/* Add Folder Form */}
                    <div className="mb-8">
                        <form onSubmit={handleAddFolder} className="flex gap-3">
                            <div className="flex-1">
                                <TextInput
                                    type="text"
                                    value={folderName}
                                    onChange={(e) => setFolderName(e.target.value)}
                                    placeholder="Enter folder name (e.g., Best, Delete, Edit)"
                                    className="w-full"
                                    disabled={loading || (session.destination_folders?.length ?? 0) >= 9}
                                />
                            </div>
                            <PrimaryButton
                                type="submit"
                                disabled={loading || !folderName.trim() || (session.destination_folders?.length ?? 0) >= 9}
                            >
                                Add Folder
                            </PrimaryButton>
                        </form>

                        {(session.destination_folders?.length ?? 0) >= 9 && (
                            <p className="text-sm text-amber-600 mt-2">
                                Maximum 9 folders reached (for keyboard shortcuts 1-9)
                            </p>
                        )}
                    </div>

                    {/* Folders List */}
                    <div className="mb-8">
                        {(session.destination_folders?.length ?? 0) > 0 ? (
                            <div className="space-y-3">
                                {(session.destination_folders || []).map((folder, index) => (
                                    <div
                                        key={folder.id}
                                        className="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                                    >
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold mr-4">
                                                {index + 1}
                                            </div>
                                            <div>
                                                <div className="flex items-center">
                                                    <svg
                                                        className="w-5 h-5 text-blue-500 mr-2"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                                    </svg>
                                                    <span className="font-medium text-gray-900">
                                                        {folder.name}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    Press "{index + 1}" to sort to this folder
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => handleRemoveFolder(folder.id)}
                                            disabled={loading}
                                            className="text-red-500 hover:text-red-700 transition-colors disabled:opacity-50"
                                            title="Remove folder"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <svg
                                    className="w-12 h-12 text-gray-400 mx-auto mb-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                                    />
                                </svg>
                                <p className="text-gray-600 font-medium mb-1">No destination folders yet</p>
                                <p className="text-sm text-gray-500">
                                    Create at least one folder to start sorting
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Keyboard Shortcuts Info */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
                        <h3 className="font-medium text-blue-900 mb-2 flex items-center">
                            <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fillRule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clipRule="evenodd"
                                />
                            </svg>
                            Keyboard Shortcuts
                        </h3>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li><kbd className="px-2 py-1 bg-white border border-blue-300 rounded text-xs font-mono">1-9</kbd> Move to folder</li>
                            <li><kbd className="px-2 py-1 bg-white border border-blue-300 rounded text-xs font-mono">←</kbd> Previous image</li>
                            <li><kbd className="px-2 py-1 bg-white border border-blue-300 rounded text-xs font-mono">→</kbd> Next / Skip image</li>
                        </ul>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex justify-between">
                        <SecondaryButton onClick={handleReset} disabled={loading}>
                            Start Over
                        </SecondaryButton>
                        <PrimaryButton
                            onClick={handleStartSorting}
                            disabled={loading || (session.destination_folders?.length ?? 0) === 0}
                            className="px-8"
                        >
                            {loading ? 'Loading...' : 'Start Sorting'}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    );
}
