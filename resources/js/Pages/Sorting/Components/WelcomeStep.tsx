import { useState } from 'react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';

interface Folder {
    id: string;
    name: string;
    parents?: string[];
}

interface Props {
    googleConnected: boolean;
    onSessionCreated: (session: any) => void;
}

export default function WelcomeStep({ googleConnected, onSessionCreated }: Props) {
    const [loading, setLoading] = useState(false);
    const [folders, setFolders] = useState<Folder[]>([]);
    const [selectedFolder, setSelectedFolder] = useState<Folder | null>(null);
    const [showFolderPicker, setShowFolderPicker] = useState(false);

    const handleConnectGoogleDrive = () => {
        window.location.href = '/google/auth';
    };

    const handleChooseFolder = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/google/folders');
            setFolders(response.data.folders);
            setShowFolderPicker(true);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to load folders');
        } finally {
            setLoading(false);
        }
    };

    const handleSelectFolder = async (folder: Folder) => {
        setSelectedFolder(folder);
        setLoading(true);
        try {
            const response = await axios.post('/sorting/session', {
                source_folder_id: folder.id,
                source_folder_name: folder.name,
            });

            onSessionCreated(response.data.session);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to create session');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div className="bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-12 text-center">
                <div className="mb-6">
                    <div className="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg">
                        <svg
                            className="w-10 h-10 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>
                <h1 className="text-4xl font-bold text-white mb-3">
                    EzSorting
                </h1>
                <p className="text-xl text-blue-100 mb-1">
                    Photo Organizer for Google Drive
                </p>
                <p className="text-sm text-blue-200">
                    Sort and organize your photos quickly and easily
                </p>
            </div>

            <div className="px-8 py-10">
                {!showFolderPicker ? (
                    <div className="max-w-2xl mx-auto">
                        <div className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-800 mb-4">
                                Welcome! Let's get started
                            </h2>
                            <p className="text-gray-600 mb-6">
                                EzSorting helps you organize your photos with a simple 3-step workflow:
                            </p>

                            <div className="space-y-4 mb-8">
                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        1
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Choose Source Folder</h3>
                                        <p className="text-sm text-gray-600">
                                            Select a folder from Google Drive containing your photos
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        2
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Create Destination Folders</h3>
                                        <p className="text-sm text-gray-600">
                                            Set up folders to organize your photos (max 9 for keyboard shortcuts)
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        3
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Start Sorting</h3>
                                        <p className="text-sm text-gray-600">
                                            Preview and sort photos using mouse or keyboard shortcuts (1-9, arrows)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {!googleConnected ? (
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div className="flex items-start">
                                    <svg
                                        className="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                    <div>
                                        <h3 className="text-sm font-medium text-yellow-800 mb-1">
                                            Connect Google Drive
                                        </h3>
                                        <p className="text-sm text-yellow-700 mb-3">
                                            You need to connect your Google Drive account to continue
                                        </p>
                                        <PrimaryButton onClick={handleConnectGoogleDrive}>
                                            Connect Google Drive
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="text-center">
                                <PrimaryButton
                                    onClick={handleChooseFolder}
                                    disabled={loading}
                                    className="px-8 py-3 text-lg"
                                >
                                    {loading ? 'Loading...' : 'Choose Source Folder'}
                                </PrimaryButton>
                            </div>
                        )}
                    </div>
                ) : (
                    <div>
                        <div className="mb-6 flex items-center justify-between">
                            <h2 className="text-2xl font-semibold text-gray-800">
                                Select Source Folder
                            </h2>
                            <button
                                onClick={() => setShowFolderPicker(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            {folders.map((folder) => (
                                <button
                                    key={folder.id}
                                    onClick={() => handleSelectFolder(folder)}
                                    disabled={loading}
                                    className="flex items-center p-4 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-150 text-left disabled:opacity-50"
                                >
                                    <svg
                                        className="w-8 h-8 text-blue-500 mr-3 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                    </svg>
                                    <span className="font-medium text-gray-900 truncate">
                                        {folder.name}
                                    </span>
                                </button>
                            ))}
                        </div>

                        {folders.length === 0 && (
                            <div className="text-center py-12 text-gray-500">
                                No folders found
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
